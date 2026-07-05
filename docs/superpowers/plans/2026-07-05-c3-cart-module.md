# Wave C3 — Cart Module

**Status:** Active — execution starts after this plan is approved.
**Scope owner:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` → Convergence Waves → C3.
**Sequence constraint:** entry criteria met (`C0` closed; `C1` closed; `C2` closed; module-boundary guardrail active and proven load-bearing on real runtime code, including the Users → legacy `App\Contracts\CartServiceInterface` cross-module bridge).

## Verified baseline (`2026-07-05`, mapping by explore agent)

Migration surface (Cart slice):

- **Transport (1 controller + 2 FormRequests):**
  - `CartController` (3 methods: show/upsertItem/removeItem; constructor-depends on 3 handlers; uses `ResolvesAuthenticatedUser` trait — stays shared; calls `$this->authorize('viewAny'/'modify', Cart::class)`; not `final`).
  - `UpsertCartItemRequest` (not `final`; `toDto(): CartUpsertItemInputDto`).
  - `RemoveCartItemRequest` (not `final`; uses `TypedValue`; `toDto(): RemoveCartItemInputDto`).
- **Routes:** `routes/api.php:46-52` — 3 routes under `/v1/cart` prefix with `active.api.user` middleware (no `throttle:`). DELETE has `->whereNumber('variantId')`. All paths/verbs/middleware stay byte-identical.
- **Application layer** (`app/Application/Cart/`): 2 command handlers + 2 readonly payloads + 1 query handler + 1 query payload + 5 DTOs. All 3 handlers depend on `App\Contracts\CartServiceInterface`.
- **Services** (`app/Services/Cart/`): 4 classes:
  - `CartService` (implements `CartServiceInterface`; constructor-depends on `CartResolver`, `CartMutationServiceInterface`, `CartResultMapper`).
  - `CartResolver` (no interface; uses `DB::transaction` + `lockForUpdate()`).
  - `CartMutationService` (implements `CartMutationServiceInterface`; reads `ProductVariant`/`Inventory`/`ProductStatus` from Catalog; `DB::transaction` + `lockForUpdate()`; `assertOwnership()`).
  - `CartResultMapper` (no interface; reads `ProductVariant`).
- **Contracts** (`app/Contracts/`): 2 cross-module interfaces today:
  - `CartServiceInterface` — 6 methods (`resolve`, `resolveForCheckout`, `upsertItem`, `removeItem`, `mergeGuestCart`, `toResultDto`). Returns `CartResultDto`.
  - `CartMutationServiceInterface` — 3 mutation methods.
- **Policy** (`app/Policies/CartPolicy.php`): `viewAny`, `modify`. Registered in `AppServiceProvider::boot()` via `Gate::policy(Cart::class, CartPolicy::class)`.
- **Models** stay shared: `Cart` (HasUuids, status → `CartStatus` cast, `$fillable` includes `user_id`, `guest_token`, `currency`, `status`, `expires_at`), `CartItem` (BelongsTo `Cart` + `ProductVariant`).
- **Enum** `App\Enums\CartStatus` stays shared (heavy cross-module usage: checkout, maintenance, admin, factories).
- **Exception** `App\Domain\Exceptions\CartException` stays shared (shared kernel).
- **DI bindings:** `ApplicationBindingsServiceProvider` owns both cart bindings (lines 37-38); they move to `CartServiceProvider`.
- **Tests that move:** `tests/Unit/CartResultMapperTest.php`, `tests/Unit/CartResolverTest.php` (move under `tests/Unit/Application/Cart/`); `tests/Unit/ApplicationServiceBindingTest.php` (will need namespace updates for cart bindings).
- **Cross-module consumers (CRITICAL — these need rewiring from `App\Contracts\*` to `App\Domains\Cart\Contracts\*`):**
  - `app/Domains/Users/Application/Commands/LoginAuthUserHandler.php:7,26,70` (calls `mergeGuestCart`).
  - `app/Services/Checkout/CheckoutPlaceOrderOrchestrator.php:9,17,30,33` (calls `mergeGuestCart` + `resolveForCheckout`; stays in legacy `App\Services\Checkout\*` until C4).
  - `app/Support/Smoke/Performance/Scenarios/CheckoutPlaceOrderPerformanceSmokeScenario.php:7,16,32`.
  - `app/Support/Smoke/Performance/PerformanceSmokeSetupFactory.php:10,43,162,163`.
  - `app/Support/Smoke/WebhookFlow/WebhookFlowScenario.php:8,38,52,53`.
  - `tests/Unit/Application/Users/LoginAuthUserHandlerTest.php:7,32,33,85`.
  - `tests/Unit/ApplicationServiceBindingTest.php:7,8,10,11,19,24`.
  - `tests/Feature/CartMutationSafetyTest.php:7,60,116,136` (`CartMutationServiceInterface`).
- **Smoke that moves:** `app/Support/Smoke/Performance/Scenarios/CartShowPerformanceSmokeScenario.php` (cart-owning); `app/Support/Smoke/ApiContract/Scenarios/CartApiContractScenario.php` (HTTP-only, no FQCN coupling — stays).
- **Frontend:** untouched. API paths (`/api/v1/cart`, `/api/v1/cart/items`, `/api/v1/cart/items/{variantId}`) locked by `docs/api/openapi.yaml` (S1).
- **Architecture guardrails requiring updates:**
  - `PolicyCompletenessMatrixGuardrailTest` — `Cart::class` + `CartPolicy::class` FQCNs.
  - `InfrastructureProviderBoundaryTest` — provider list (add `CartServiceProvider`).
  - `ModularMonolithSkeletonGuardrailTest` — already lists `'Cart'` (no change).
  - `ModuleBoundaryGuardrailTest` — verify the first real cross-module `Domains\<Other>\Contracts\` import (Users → Cart) passes.

## Resolved design decisions (`2026-07-05`)

1. **Module name: `Cart`.** Confirmed by `docs/DOMAIN_MAP.md` and `app/Domains/Cart/README.md` placeholder.
2. **Subfolder structure:** `Controllers`, `Application/{Commands,Queries,Dto}`, `Services`, `Contracts`, `Policies`. No `Repositories/` (none exist — services hit Eloquent directly). No `Middleware/`, `Infrastructure/`, `Support/` (none cart-specific).
3. **Contract naming:** preserve existing `CartServiceInterface` + `CartMutationServiceInterface` names (no rename to `CartService`). Rationale: minimal-diff across 6+ cross-module call sites; preserves the existing name that `LoginAuthUserHandler`, `CheckoutPlaceOrderOrchestrator`, smoke scenarios, and tests already reference. C1's `CatalogReadService` rename was justified (the existing class was `CatalogService` with no `Interface` suffix); here both interface and implementation already coexist with the `Interface` suffix, so renaming would add churn without architectural value. The contracts move physically into `app/Domains/Cart/Contracts/` with the same class names.
4. **`CartMutationServiceInterface` fate:** move into `app/Domains/Cart/Contracts/` as a module-internal contract (C1 precedent: `CatalogProductReadRepository` is module-internal but still a contract). Preserves the existing test surface (`CartMutationSafetyTest` resolves via the contract).
5. **`CartPolicy` location:** move into `app/Domains/Cart/Policies/CartPolicy.php` (parallel to `Contracts/`, `Application/`, `Services/`). `Gate::policy(Cart::class, CartPolicy::class)` registration moves from `AppServiceProvider::boot()` to `CartServiceProvider::boot()`. The `PolicyCompletenessMatrixGuardrailTest` import updates atomically.
6. **ServiceProvider: `CartServiceProvider`.** New file at `app/Domains/Cart/CartServiceProvider.php`. Binds `CartServiceInterface` → `CartService` and `CartMutationServiceInterface` → `CartMutationService`; registers `Gate::policy(Cart::class, CartPolicy::class)` in `boot()`. Precedent: C1's `CatalogServiceProvider` + C2's `UsersServiceProvider`.
7. **Models stay shared:** `Cart`, `CartItem` remain in `app/Models/` per C0/C1/C2 precedent (model-ownership wave is post-C7). `CartStatus` enum stays in `App\Enums\` (heavy cross-module usage). `CartException` stays in `App\Domain\Exceptions\` (shared kernel exception).
8. **`final` on move (AGENTS.md default):** `CartController`, `UpsertCartItemRequest`, `RemoveCartItemRequest`, `CartPolicy` → `final`.
9. **Stable operational contracts preserved:**
   - HTTP wire (`/api/v1/cart`, `/api/v1/cart/items`, `/api/v1/cart/items/{variantId}`) — paths, verbs, request schemas, response schemas, status codes, middleware (`active.api.user`).
   - `CartStatus` enum literal values (`active`, `checked_out`, `abandoned`) — wire + DB + cross-module.
   - Guest cart token resolution (`?guest_token=` query OR `X-Cart-Token` header) — both channels preserved.
   - `CartException` literal messages mapped by `ApiExceptionRenderer`.
   - Locking semantics (`lockForUpdate` on `Cart`, `CartItem`, `User` within `DB::transaction`) — locked by `CartMutationSafetyTest`.
   - Ownership guard semantics (`CartMutationService::assertOwnership()`).
   - Maintenance cleanup config keys + CLI option names (not moved by C3).
10. **First real cross-module `Domains\<Other>\Contracts\` import:** the Users → Cart import in `LoginAuthUserHandler` is the first non-trivial case for `ModuleBoundaryGuardrailTest::test_cross_module_imports_go_through_contracts_only`. The C3 implementation verifies the contract namespace passes the guardrail before bulk-updating callers.

## Invariants to preserve

1. **Wire contract byte-identical.** All 3 cart endpoints: paths, verbs, request schemas, response schemas, status codes, middleware (`active.api.user`), guest token resolution. Verified by `OpenApiConformanceFeatureTest` (S1).
2. **Locking semantics unchanged.** `DB::transaction` + `lockForUpdate()` on `Cart`, `CartItem`, `User` rows in `CartResolver::resolve`, `CartResolver::resolveForCheckout`, `CartMutationService::upsertItem`/`removeItem`/`mergeGuestCart`. Locked by `CartMutationSafetyTest`.
3. **Ownership assertions unchanged.** `CartMutationService::assertOwnership()`.
4. **`CartStatus` literal values unchanged.** Used in serialized cart, maintenance queries, Account/Admin order reads.
5. **Cross-module contract method signatures unchanged.** `CartServiceInterface` and `CartMutationServiceInterface` method signatures preserved verbatim (only the namespace moves).
6. **Frontend untouched.** Zero FQCN coupling.
7. **Eloquent models stay shared.** `Cart`, `CartItem`, `CartStatus`, `CartException` remain under legacy-bridge allowance.
8. **Guardrail extended, not weakened.** `ModuleBoundaryGuardrailTest` passes with the first real cross-module `Domains\<Other>\Contracts\` import (Users → Cart).

## Out of scope (deliberate)

- **Checkout slice.** Migrates in C4. `CheckoutPlaceOrderOrchestrator` stays in legacy `App\Services\Checkout\*`; its import of `CartServiceInterface` updates to the new module namespace (cross-module, allowlisted).
- **Admin slice.** No direct cart coupling today (verified).
- **Eloquent model ownership.** `Cart`, `CartItem` stay in `app/Models/`.
- **Enum ownership.** `CartStatus` stays in `App\Enums\` (heavy cross-module usage).
- **Exception ownership.** `CartException` stays in `App\Domain\Exceptions\`.
- **Maintenance cleanup commands.** Stay global (`App\Maintenance\*` or wherever they live); they reference `CartStatus` and `Cart` model only.
- **Decimal/float money migration.** Preserve existing float wire shape.
- **Frontend changes.** Zero.

## Implementation slices

### Slice 1 — Move implementation files (git mv for history)

Create module subfolders:

- `app/Domains/Cart/{Controllers,Application/Commands,Application/Queries,Application/Dto,Services,Contracts,Policies}`.

Move via `git mv`:

- **Controller (1):** `app/Http/Controllers/Api/V1/CartController.php` → `app/Domains/Cart/Controllers/CartController.php`.
- **FormRequests (2):** `app/Http/Requests/Cart/{UpsertCartItemRequest,RemoveCartItemRequest}.php` → `app/Domains/Cart/Controllers/`.
- **Application (11 files):** `app/Application/Cart/Commands/{UpsertCartItemCommand,UpsertCartItemHandler,RemoveCartItemCommand,RemoveCartItemHandler}.php` → `app/Domains/Cart/Application/Commands/`; `app/Application/Cart/Queries/{GetCurrentCartQuery,GetCurrentCartHandler}.php` → `app/Domains/Cart/Application/Queries/`; `app/Application/Cart/Dto/{CartResultDto,CartItemResultDto,CartUpsertItemInputDto,RemoveCartItemInputDto,CartSummaryResultDto}.php` → `app/Domains/Cart/Application/Dto/`.
- **Services (4):** `app/Services/Cart/{CartService,CartResolver,CartMutationService,CartResultMapper}.php` → `app/Domains/Cart/Services/`.
- **Contracts (2):** `app/Contracts/{CartServiceInterface,CartMutationServiceInterface}.php` → `app/Domains/Cart/Contracts/`.
- **Policy (1):** `app/Policies/CartPolicy.php` → `app/Domains/Cart/Policies/CartPolicy.php`.
- **Smoke (1):** `app/Support/Smoke/Performance/Scenarios/CartShowPerformanceSmokeScenario.php` stays (it's cart-consuming, not cart-owning — but its constructor dep is `CartServiceInterface`; only the import updates, no `git mv`). Reconsider: the file is HTTP-only smoke infrastructure; it stays under `app/Support/Smoke/`. **No `git mv` for smoke.**
- **Tests that move (2 unit):** `tests/Unit/CartResultMapperTest.php`, `tests/Unit/CartResolverTest.php` → `tests/Unit/Application/Cart/`.

### Slice 2 — Namespace + use-statement updates

For every moved file, update:

1. `namespace` declaration to the new `App\Domains\Cart\*` path.
2. Every `use` statement referencing the moved classes.
3. Add `final` to `CartController`, `UpsertCartItemRequest`, `RemoveCartItemRequest`, `CartPolicy`.

### Slice 3 — Wiring

- `routes/api.php:15` — update controller import.
- `bootstrap/providers.php` — add `App\Domains\Cart\CartServiceProvider::class`.
- Create `app/Domains/Cart/CartServiceProvider.php` — binds 2 contracts; registers `Gate::policy(Cart::class, CartPolicy::class)` in `boot()`.
- `app/Providers/ApplicationBindingsServiceProvider.php` — remove the 2 cart bindings + 4 imports (cart contract imports + cart service imports).
- `app/Providers/AppServiceProvider.php` — remove `Gate::policy(Cart::class, CartPolicy::class)` + the `Cart` model + `CartPolicy` imports (lines 7, 13, 38).
- `psalm.xml` — add `app/Domains/Cart/Services` and `app/Domains/Cart/Application/Dto` to `TooManyTemplateParams` + `InvalidDocblock` suppressions if needed (cart uses `Cart` model collection shapes).

### Slice 4 — Cross-module caller updates

Update 8+ call sites that currently import `App\Contracts\CartServiceInterface` (or `CartMutationServiceInterface`) to the new `App\Domains\Cart\Contracts\*` namespace:

- `app/Domains/Users/Application/Commands/LoginAuthUserHandler.php` (Users module — first real cross-module `Domains\<Other>\Contracts\` import).
- `app/Services/Checkout/CheckoutPlaceOrderOrchestrator.php` (legacy `App\Services\Checkout\*`; stays until C4).
- `app/Support/Smoke/Performance/Scenarios/CheckoutPlaceOrderPerformanceSmokeScenario.php`.
- `app/Support/Smoke/Performance/PerformanceSmokeSetupFactory.php`.
- `app/Support/Smoke/WebhookFlow/WebhookFlowScenario.php`.
- `tests/Unit/Application/Users/LoginAuthUserHandlerTest.php`.
- `tests/Unit/ApplicationServiceBindingTest.php`.
- `tests/Feature/CartMutationSafetyTest.php`.

### Slice 5 — Guardrail test updates

- `tests/Unit/Architecture/PolicyCompletenessMatrixGuardrailTest.php` — `CartPolicy` FQCN.
- `tests/Unit/Architecture/InfrastructureProviderBoundaryTest.php` — add `CartServiceProvider` to provider list (both assertions).
- `tests/Unit/Architecture/ModuleBoundaryGuardrailTest.php` — verify it passes with the new cross-module Users → Cart import.

### Slice 6 — Tests that move + relocation test

- Update namespaces + imports in 2 unit tests that move (`CartResultMapperTest`, `CartResolverTest`) to `tests/Unit/Application/Cart/`.
- Add `tests/Feature/CartModuleRelocationTest.php` (precedent: C1's `CatalogModuleRelocationTest`, C2's `UsersModuleRelocationTest`) — locks the 2 contract bindings, the controller namespace via route resolution, the `Gate::policy()` resolution for `Cart::class` → module `CartPolicy`.

### Slice 7 — Module README

- `app/Domains/Cart/README.md` — replace placeholder with active module documentation following C1's template: active subfolders, public contract surface (the 2 contracts + method purposes), operational contracts (HTTP wire, `CartStatus` literals, guest token resolution channels, locking semantics, ownership guard, policy), migration state.

### Slice 8 — Docs sync

- `docs/REPO_MAP.md` — Cart row: `complete (C3)`; list contract surface.
- `docs/DOMAIN_MAP.md` — Cart migration state: `[migration: complete C3]`; cross-reference the module location.
- `docs/ARCHITECTURE_REFACTOR_NEXT.md` — mark `C3` closed; append closed-block definition; append change-control entry.
- `docs/REFACTORING_EXECUTION_PLAN.md` — append full C3 entry.

### Slice 9 — Quality gate

Run the canonical sequence strictly in order, one command at a time:

1. `composer run lint`
2. `composer run analyse`
3. `php artisan test`
4. `npm run lint`
5. `npm run lint:ox`
6. `npm run format:ox:check`
7. `npm run type-check`
8. `npm run test`
9. `npm run build`

Plus route-smoke (controllers/middleware changed): `php artisan optimize:clear` + `php artisan route:list --path=api/v1/cart`.

## Definition of done

1. Cart slice (transport + application + services + contracts + policy) lives under `app/Domains/Cart/*` with namespace moves; old files removed.
2. `CartServiceProvider` registered in `bootstrap/providers.php`; binds the 2 contracts and owns `Gate::policy(Cart::class, CartPolicy::class)`.
3. `ApplicationBindingsServiceProvider` no longer binds cart contracts.
4. `AppServiceProvider` no longer registers `Gate::policy(Cart::class, CartPolicy::class)`.
5. Cross-module callers (Users `LoginAuthUserHandler`, Checkout orchestrator, smoke scenarios, tests) import cart contracts from `App\Domains\Cart\Contracts\*` (first real cross-module `Domains\<Other>\Contracts\` import — `ModuleBoundaryGuardrailTest` validates this path).
6. `/api/v1/cart/*` wire contract byte-identical (`OpenApiConformanceFeatureTest` green).
7. Locking semantics, ownership assertions, `CartStatus` literals, guest token resolution channels unchanged.
8. `ModuleBoundaryGuardrailTest` green (legacy-bridge allowlist effectively shrinks further — `App\Contracts\CartServiceInterface` / `CartMutationServiceInterface` no longer in legacy paths; `App\Services\Cart\*` no longer in legacy paths).
9. Cart README documents the active contract surface; skeleton guardrail still passes.
10. `docs/ARCHITECTURE_REFACTOR_NEXT.md` records `C3` as closed; `docs/REFACTORING_EXECUTION_PLAN.md` records the work and checks.
11. The quality gate is green and the executed checks are reported.
