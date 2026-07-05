# Wave C4 — Checkout Module

**Status:** Active — execution starts after this plan is approved.
**Scope owner:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` → Convergence Waves → C4.
**Sequence constraint:** entry criteria met (`C0` closed; `C1` Catalog closed; `C2` Users closed; `C3` Cart closed; cross-module `Domains\<Other>\Contracts\` import path proven load-bearing on Users → Cart bridge).

## Verified baseline (`2026-07-05`, mapping by explore agent)

Migration surface (Checkout slice):

- **Transport (1 controller + 2 FormRequests):**
  - `CheckoutController` (2 methods: placeOrder/pay; constructor-depends on `PlaceCheckoutOrderHandler` + `InitiateCheckoutPaymentHandler`; uses `ResolvesAuthenticatedUser` trait — stays shared; calls `$this->authorize('view', $order)` once; not `final`).
  - `PlaceOrderRequest` (not `final`; `toDto(): CheckoutPlaceOrderInputDto`; `idempotencyKey(): string`).
  - `InitiatePaymentRequest` (already `final`; reads `Idempotency-Key` header in `prepareForValidation`).
- **Routes:** `routes/api.php:54-55,57-59` — 2 routes:
  - `POST checkout/place-order` — middleware `['active.api.user', 'throttle:checkout', 'idempotency.key']`.
  - `POST checkout/orders/{order}/pay` — middleware `['throttle:checkout', 'idempotency.key']` inside `auth:sanctum` + `active.api.user` group.
- **Application layer** (`app/Application/Checkout/`): 2 command handlers + 2 readonly payloads + 12 DTOs. No sub-Contracts folder.
- **Services** (`app/Services/Checkout/`): 9 classes + 4 service DTOs:
  - `CheckoutService` (implements `CheckoutServiceInterface`; 8 constructor collaborators; `placeOrder` wraps in `DB::transaction` with multiple `lockForUpdate` calls).
  - `CheckoutPlaceOrderOrchestrator` (already imports `App\Domains\Cart\Contracts\CartServiceInterface` from C3; also imports `App\Contracts\CheckoutServiceInterface` + `App\Services\Payment\PaymentService` — last one stays legacy bridge until C6).
  - `CheckoutOrderWriter` (initial state assignment via `forceFill` — `Order`/`Payment`/`Shipment` excluded from `$fillable` per `SensitiveStateFillableGuardrailTest`).
  - `CheckoutOrderFinalizer` (sets `CartStatus::CHECKED_OUT` via `$cart->update` — `Cart::status` IS in `$fillable`, so direct update works; dispatches `OrderPlaced` event).
  - `CheckoutIdempotencyGuard` (5-branch resolution; reads `config('checkout.idempotency.pending_minutes')`).
  - `CheckoutDiscountResolver` (R2 promotion arithmetic, guardrail-locked signature).
  - `CheckoutShippingCostResolver` (**interface**, currently in `Services/` — moves to `Contracts/`).
  - `FreeCheckoutShippingCostResolver` (implements `CheckoutShippingCostResolver`).
  - `CheckoutInventoryAllocator`, `CheckoutCartPreparer`, `CheckoutRequestIdentityResolver`.
- **Contract** (`app/Contracts/`): 1 interface today — `CheckoutServiceInterface` (`placeOrder` only; consumed by orchestrator + smoke).
- **Idempotency surface:**
  - HTTP middleware `EnsureIdempotencyKeyMiddleware` (alias `idempotency.key` in `bootstrap/app.php:30`; requires non-empty `Idempotency-Key` header; trims and re-sets).
  - Application-level `CheckoutIdempotencyGuard` (5-branch).
  - Config `config/checkout.php` (env vars `CHECKOUT_IDEMPOTENCY_PENDING_MINUTES`, `CHECKOUT_IDEMPOTENCY_COMPLETED_HOURS`).
  - Model `CheckoutIdempotency` (stays shared at `App\Models\` per C0 model-ownership allowance).
- **No policies move** in C4. `OrderPolicy` stays at `App\Policies\` (C5 territory — authorizes `Order` model, not Checkout concept).
- **No repositories exist** for checkout (`RepositoryReadBoundaryTest` explicitly forbids `OrderRepository`).
- **Models stay shared:** `Order`, `OrderItem`, `Payment`, `Shipment`, `CheckoutIdempotency`. Per C0/C1/C2/C3 precedent (post-C7 model-ownership wave).
- **Enums stay shared:** `OrderStatus`, `PaymentStatus`, `ShipmentStatus` — heavy cross-module usage (cart, checkout, admin, webhooks, listeners, smoke, factories).
- **Events stay shared:** `OrderPlaced` (emitted by `CheckoutOrderFinalizer`, listened cross-module by `QueueOrderSideEffects`). `OrderStatusChanged`, `PaymentStatusChanged`, `ShipmentStatusChanged` emitted by admin/webhook appliers — C5/C6/C7.
- **Exception stays shared:** `CheckoutException` (heavy cross-module usage by Listeners/Smoke). Shared kernel.
- **DI bindings:** `ApplicationBindingsServiceProvider` owns both checkout bindings (lines 32-33: `CheckoutShippingCostResolver` → `FreeCheckoutShippingCostResolver` + `CheckoutServiceInterface` → `CheckoutService`); they move to `CheckoutServiceProvider`.
- **Rate limiter:** `AppServiceProvider::boot()` owns `RateLimiter::for('checkout', ...)` (6/min by user or IP). Moves to `CheckoutServiceProvider::boot()`.
- **Tests that move (5 unit):** `tests/Unit/Checkout/CheckoutDiscountResolverTest.php`, `tests/Unit/Checkout/CheckoutIdempotencyRetentionConfigTest.php`, `tests/Unit/CheckoutOrderFinalizerTest.php`, `tests/Unit/CheckoutRequestIdentityResolverTest.php`, `tests/Unit/CheckoutShippingCostResolverBindingTest.php` → `tests/Unit/Application/Checkout/`.
- **Cross-module consumers (CRITICAL — these need rewiring from `App\Contracts\*` / `App\Services\Checkout\*` / `App\Application\Checkout\*` to `App\Domains\Checkout\*`):**
  - `app/Support/Smoke/Performance/Scenarios/CheckoutPlaceOrderPerformanceSmokeScenario.php` (imports `App\Contracts\CheckoutServiceInterface`).
  - `app/Support/Smoke/WebhookFlow/WebhookFlowScenario.php` (imports `App\Contracts\CheckoutServiceInterface`).
  - `app/Support/Smoke/Performance/PerformanceSmokeSetupFactory.php` (imports `App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto`).
  - `app/Support/Smoke/Performance/Dto/PerformanceSmokeContextDto.php` (imports `App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto`).
  - `tests/Unit/ApplicationServiceBindingTest.php` (imports checkout contract + service).
  - Internal-to-module: `CheckoutPlaceOrderOrchestrator` imports `App\Contracts\CheckoutServiceInterface` (becomes intra-module after C4 — rewires to module namespace).
- **Watch-item verified:** `CheckoutOrderFinalizer::finalize()` calls `$cart->update(['status' => CartStatus::CHECKED_OUT->value])`. `Cart::status` IS in `$fillable` (verified). Direct update works correctly. No change needed in C4.
- **Frontend:** untouched. URL-only coupling (`POST /checkout/place-order`).
- **Architecture guardrails requiring updates:**
  - `InfrastructureProviderBoundaryTest` — add `CheckoutServiceProvider` to provider list.
  - `ApplicationCheckoutCommandBoundaryTest` — discovery path + namespace literal.
  - `CheckoutIdempotencyAndPromotionArithmeticGuardrailTest` — 4 literal paths.
  - `AddressPayloadBoundaryGuardrailTest` — DTO import + 3 construction-site paths.
  - `LegacyPayloadArtifactGuardrailTest` — namespace literal.
- **Scope clarification from roadmap (`docs/ARCHITECTURE_REFACTOR_NEXT.md:540-542`):** "Move checkout transport, `Application/Checkout/*`, `Services/Checkout/*` collaborators, idempotency guard/config into `app/Domains/Checkout`. Cross-module needs (catalog variants, cart resolution, order writing) go through module contracts defined in C0." **Webhook controllers + `Application/Webhook/*` + webhook Jobs stay in legacy bridge for C7.** Transition policies + `AdminOrderService` stay for C5/C6.

## Resolved design decisions (`2026-07-05`)

1. **Module name: `Checkout`.** Confirmed by `docs/DOMAIN_MAP.md` and `app/Domains/Checkout/README.md` placeholder.
2. **Subfolder structure:** `Controllers`, `Application/{Commands,Dto}`, `Services`, `Services/Dto`, `Contracts`, `Middleware`. No `Repositories/` (none exist). No `Policies/` (OrderPolicy stays for C5).
3. **Contract naming:** preserve existing `CheckoutServiceInterface` name (no rename). Rationale: minimal-diff at 2+ cross-module call sites; matches C3 precedent (`CartServiceInterface` preserved). The contract moves physically to `app/Domains/Checkout/Contracts/`.
4. **`CheckoutShippingCostResolver` interface fate:** relocate to `app/Domains/Checkout/Contracts/CheckoutShippingCostResolver.php` (cleaner than interface-in-services pattern). Precedent: C1's `CatalogReadService` lives under `Contracts/`. `FreeCheckoutShippingCostResolver` (the implementation) moves to `Services/`.
5. **`CheckoutIdempotency` model stays shared** at `App\Models\` per C0 model-ownership allowance (mirrors C3 `Cart`/`CartItem` decision). Heavy cross-module usage potential (admin reporting, future reconciliation jobs).
6. **ServiceProvider: `CheckoutServiceProvider`.** New file at `app/Domains/Checkout/CheckoutServiceProvider.php`. Binds 2 contracts; owns `RateLimiter::for('checkout', ...)` in `boot()`. Precedent: C1/C2/C3.
7. **Models stay shared:** `Order`, `OrderItem`, `Payment`, `Shipment`, `CheckoutIdempotency` remain in `app/Models/`.
8. **Enums stay shared:** `OrderStatus`, `PaymentStatus`, `ShipmentStatus` remain in `App\Enums\` (heavy cross-module usage).
9. **Events stay shared:** `OrderPlaced` (and the other 3 status-changed events) stay at `App\Events\`. Emitted from multiple contexts (Checkout, Admin, Webhooks); consolidation is C5+.
10. **Exception stays shared:** `CheckoutException` stays at `App\Domain\Exceptions\`.
11. **`OrderPolicy` stays** at `app/Policies/`. C5 will move it (authorizes `Order` model, owned by Orders module).
12. **`OrderPaymentStatusResolver` stays shared** at `App\Domain\Order\`. Used cross-module by listeners and `DispatchShipmentJob`.
13. **`final` on move (AGENTS.md default):** `CheckoutController`, `PlaceOrderRequest` → `final`. `InitiatePaymentRequest` already `final`.
14. **Middleware relocation:** `EnsureIdempotencyKeyMiddleware` → `app/Domains/Checkout/Middleware/EnsureIdempotencyKeyMiddleware.php`. Alias `idempotency.key` preserved (FQCN updated in `bootstrap/app.php`). Mirrors C2 `active.api.user` move.
15. **Config stays at root:** `config/checkout.php` stays (Laravel convention; config files are global). Only the guardrail test path literal continues to resolve.
16. **`InitiateCheckoutPaymentHandler` keeps `App\Services\Payment\PaymentService`** direct import (legacy bridge allowance until C6). Option (a) per design pass — minimal-diff; introducing a `PaymentInitiation` contract now would be premature abstraction.
17. **`CheckoutPlaceOrderOrchestrator` rewires `App\Contracts\CheckoutServiceInterface`** to `App\Domains\Checkout\Contracts\CheckoutServiceInterface` (intra-module after the move; still uses the contract for testability + binding).
18. **Stable operational contracts preserved:**
    - HTTP wire (`POST /checkout/place-order` returns `201` with order+payment envelope; `POST /checkout/orders/{order}/pay` returns order+payment envelope). Locked by feature tests.
    - Idempotency semantics: `Idempotency-Key` header required; scope_key = `user:<id>` or `guest:<token>`; payload-mismatch throws `CheckoutException::idempotencyPayloadMismatch`; replay returns original order; retention windows `pending_minutes=30` / `completed_hours=24` defaults (env-overridable).
    - Rate limiting: `throttle:checkout` = 6/min by user-id or IP (must stay byte-identical).
    - Money semantics: subtotal, discount_total, shipping_total, total as `decimal:2` strings at Eloquent boundary.
    - Address payload boundary: `{line1, city, country, postcode}` closed shape (locked by `AddressPayloadBoundaryGuardrailTest`).
    - Initial state tuple: `Order{status: pending, payment_status: pending, shipment_status: pending}` via `forceFill` (NOT mass assignment; locked by `SensitiveStateFillableGuardrailTest`).
    - Locking semantics: `Cart::lockForUpdate()` + `CheckoutIdempotency::lockForUpdate()` + `Inventory::lockForUpdate()` + `Coupon::lockForUpdate()` + `Promotion::lockForUpdate()` all within `DB::transaction()` in `CheckoutService::placeOrder`.
    - Event dispatch: `OrderPlaced` dispatched via `event()` inside `finalize()`, implements `ShouldDispatchAfterCommit`. Side-effect jobs dispatch via `->afterCommit()`.
    - Cart transition: `CartStatus::CHECKED_OUT` set by `CheckoutOrderFinalizer::finalize()` via `$cart->update(...)` (Cart status IS fillable).

## Invariants to preserve

1. **Wire contract byte-identical.** Both checkout endpoints: paths, verbs, request schemas, response schemas (order+payment envelope), status codes (`201` on place, `200` on pay replay), middleware (`active.api.user`, `throttle:checkout`, `idempotency.key`).
2. **Idempotency semantics unchanged.** Header validation, scope_key construction, 5-branch guard resolution, retention windows, payload-mismatch behavior.
3. **Locking semantics unchanged.** `lockForUpdate` on `Cart`/`CheckoutIdempotency`/`Inventory`/`Coupon`/`Promotion` within `DB::transaction`.
4. **Money semantics unchanged.** Decimal precision, R2 promotion arithmetic.
5. **Cross-module contract method signatures unchanged.** `CheckoutServiceInterface::placeOrder` signature preserved verbatim (only the namespace moves).
6. **Frontend untouched.** Zero FQCN coupling.
7. **Eloquent models stay shared.** `Order`, `OrderItem`, `Payment`, `Shipment`, `CheckoutIdempotency`.
8. **Enums, events, exceptions, policies stay shared** under legacy-bridge allowance.
9. **`OrderPlaced` event dispatch preserved** (still emitted via `event()` from `finalize()`; still implements `ShouldDispatchAfterCommit`; listeners stay registered in `EventServiceProvider`).
10. **Guardrail extended, not weakened.**

## Out of scope (deliberate)

- **Webhook slice** (`PaymentWebhookController`, `ShippingWebhookController`, `Application/Webhook/*`, `Services/Webhook/*`, `ProcessPaymentWebhookJob`). Migrates in C7.
- **Order lifecycle / transition policies** (`OrderStatusTransitionPolicy`, `AdminOrderService`, `OrderInventoryReleaseService`, account/admin order reads). Migrate in C5.
- **Payment slice** (`PaymentService`, `PaymentStatusTransitionPolicy`, `PaymentWebhookTransitionApplier`, gateway contracts). Migrate in C6.
- **Shipping slice** (`ShippingService`, `ShipmentStatusTransitionPolicy`, `ShipmentWebhookTransitionApplier`). Migrate in C6/C7.
- **Eloquent model ownership.** All models stay shared.
- **Enum ownership.** All enums stay shared.
- **Event ownership.** All events stay shared.
- **`OrderPolicy`.** Stays for C5.
- **Frontend changes.** Zero.

## Implementation slices

### Slice 1 — Move implementation files (git mv for history)

Create module subfolders:

- `app/Domains/Checkout/{Controllers,Application/Commands,Application/Dto,Services,Services/Dto,Contracts,Middleware}`.
- `tests/Unit/Application/Checkout/`.

Move via `git mv`:

- **Controller (1):** `app/Http/Controllers/Api/V1/CheckoutController.php` → `app/Domains/Checkout/Controllers/CheckoutController.php`.
- **FormRequests (2):** `app/Http/Requests/Checkout/{PlaceOrderRequest,InitiatePaymentRequest}.php` → `app/Domains/Checkout/Controllers/`.
- **Application (16 files):** 4 files in `app/Application/Checkout/Commands/` → `app/Domains/Checkout/Application/Commands/`; 12 files in `app/Application/Checkout/Dto/` → `app/Domains/Checkout/Application/Dto/`.
- **Services (9 files):** `app/Services/Checkout/{CheckoutService,CheckoutPlaceOrderOrchestrator,CheckoutOrderWriter,CheckoutOrderFinalizer,CheckoutIdempotencyGuard,CheckoutDiscountResolver,FreeCheckoutShippingCostResolver,CheckoutInventoryAllocator,CheckoutCartPreparer,CheckoutRequestIdentityResolver}.php` → `app/Domains/Checkout/Services/`.
- **Service DTOs (4):** `app/Services/Checkout/Dto/*.php` → `app/Domains/Checkout/Services/Dto/`.
- **Contract (1) + interface move:** `app/Contracts/CheckoutServiceInterface.php` → `app/Domains/Checkout/Contracts/CheckoutServiceInterface.php`.
- **Interface relocation:** `app/Services/Checkout/CheckoutShippingCostResolver.php` → `app/Domains/Checkout/Contracts/CheckoutShippingCostResolver.php` (interface moves from `Services/` to `Contracts/`).
- **Middleware (1):** `app/Http/Middleware/EnsureIdempotencyKeyMiddleware.php` → `app/Domains/Checkout/Middleware/EnsureIdempotencyKeyMiddleware.php`.
- **Tests that move (5 unit):** `tests/Unit/Checkout/{CheckoutDiscountResolver,CheckoutIdempotencyRetentionConfig}Test.php`, `tests/Unit/Checkout{OrderFinalizer,RequestIdentityResolver,ShippingCostResolverBinding}Test.php` → `tests/Unit/Application/Checkout/`.

### Slice 2 — Namespace + use-statement updates + final on 2 classes

For every moved file:

1. `namespace` declaration to the new `App\Domains\Checkout\*` path.
2. Every `use` statement referencing the moved classes.
3. Add `final` to `CheckoutController`, `PlaceOrderRequest` (`InitiatePaymentRequest` already final).

### Slice 3 — Wiring

- `routes/api.php:16` — controller import switched.
- `bootstrap/app.php:30` — middleware FQCN updated.
- `bootstrap/providers.php` — add `App\Domains\Checkout\CheckoutServiceProvider::class` (after `CartServiceProvider`, before `GatewayServiceProvider`).
- Create `app/Domains/Checkout/CheckoutServiceProvider.php`:
  - `register()`: binds `CheckoutServiceInterface` → `CheckoutService` and `CheckoutShippingCostResolver` → `FreeCheckoutShippingCostResolver`.
  - `boot()`: owns `RateLimiter::for('checkout', ...)`.
- `app/Providers/ApplicationBindingsServiceProvider.php` — remove the 2 checkout bindings + 3 imports.
- `app/Providers/AppServiceProvider.php` — remove `RateLimiter::for('checkout', ...)` (lines 42-47).
- `psalm.xml` — add `app/Domains/Checkout/Services` and `app/Domains/Checkout/Application/Dto` to `TooManyTemplateParams` + `InvalidDocblock` suppressions.

### Slice 4 — Cross-module caller updates

- `app/Support/Smoke/Performance/Scenarios/CheckoutPlaceOrderPerformanceSmokeScenario.php` — rewire `App\Contracts\CheckoutServiceInterface` → `App\Domains\Checkout\Contracts\CheckoutServiceInterface`.
- `app/Support/Smoke/WebhookFlow/WebhookFlowScenario.php` — same.
- `app/Support/Smoke/Performance/PerformanceSmokeSetupFactory.php` — rewire `App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto` → `App\Domains\Checkout\Application\Dto\...`.
- `app/Support/Smoke/Performance/Dto/PerformanceSmokeContextDto.php` — same.
- `tests/Unit/ApplicationServiceBindingTest.php` — rewire contract + service imports.
- Internal to module: `CheckoutPlaceOrderOrchestrator` rewires `App\Contracts\CheckoutServiceInterface` → module namespace (handled in Slice 2 as part of namespace bulk-update).

### Slice 5 — Guardrail test updates

- `tests/Unit/Architecture/InfrastructureProviderBoundaryTest.php` — add `CheckoutServiceProvider::class` to provider list (both assertions).
- `tests/Unit/Architecture/ApplicationCheckoutCommandBoundaryTest.php` — discovery path → `app/Domains/Checkout/Application/Commands`; namespace literal → `App\Domains\Checkout\Application\Commands\`.
- `tests/Unit/Architecture/CheckoutIdempotencyAndPromotionArithmeticGuardrailTest.php` — 4 literal paths updated.
- `tests/Unit/Architecture/AddressPayloadBoundaryGuardrailTest.php` — DTO import + 3 construction-site paths.
- `tests/Unit/Architecture/LegacyPayloadArtifactGuardrailTest.php` — namespace literal.

### Slice 6 — Tests that move + relocation test

- Update namespaces + imports in 5 unit tests that move → `tests/Unit/Application/Checkout/`.
- Add `tests/Feature/CheckoutModuleRelocationTest.php` (precedent: C1/C2/C3) — locks the 2 contract bindings, controller namespace via route resolution, `idempotency.key` middleware alias FQCN, `throttle:checkout` rate limiter registration, provider registration.

### Slice 7 — Module README

- `app/Domains/Checkout/README.md` — replace placeholder with active module documentation following C1/C2/C3 template.

### Slice 8 — Docs sync

- `docs/REPO_MAP.md` — Checkout row: `complete (C4)`; list contract surface.
- `docs/DOMAIN_MAP.md` — Checkout migration state: `[migration: complete C4]`.
- `docs/ARCHITECTURE_REFACTOR_NEXT.md` — mark `C4` closed; append closed-block definition; append change-control entry.
- `docs/REFACTORING_EXECUTION_PLAN.md` — append full C4 entry.

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

Plus route-smoke (controllers/middleware changed): `php artisan optimize:clear` + `php artisan route:list --path=api/v1/checkout`.

## Definition of done

1. Checkout slice (transport + application + services + contracts + idempotency middleware) lives under `app/Domains/Checkout/*` with namespace moves; old files removed.
2. `CheckoutServiceProvider` registered in `bootstrap/providers.php`; binds the 2 contracts and owns `RateLimiter::for('checkout', ...)`.
3. `ApplicationBindingsServiceProvider` no longer binds checkout contracts.
4. `AppServiceProvider` no longer registers `RateLimiter::for('checkout', ...)`.
5. Cross-module callers (smoke scenarios, smoke infra, tests) import checkout contracts through `App\Domains\Checkout\Contracts\*`.
6. `/api/v1/checkout/*` wire contract byte-identical (feature tests green).
7. Idempotency semantics, locking semantics, money semantics, address payload boundary, initial state tuple, event dispatch semantics — all unchanged.
8. `ModuleBoundaryGuardrailTest` green; legacy-bridge allowlist effectively shrinks further (`App\Contracts\CheckoutServiceInterface` and `App\Services\Checkout\*` no longer in legacy paths).
9. Checkout README documents the active contract surface; skeleton guardrail still passes.
10. `docs/ARCHITECTURE_REFACTOR_NEXT.md` records `C4` as closed; `docs/REFACTORING_EXECUTION_PLAN.md` records the work and checks.
11. The quality gate is green and the executed checks are reported.
