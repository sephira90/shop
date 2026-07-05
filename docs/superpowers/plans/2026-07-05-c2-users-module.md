# Wave C2 — Users Module

**Status:** Active — execution starts after this plan is approved.
**Scope owner:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` → Convergence Waves → C2.
**Sequence constraint:** entry criteria met (`C0` closed; `C1` closed; module-boundary guardrail active and proven load-bearing on real runtime code).

## Verified baseline (`2026-07-05`, mapping by explore agent)

Migration surface (Auth + Account Orders slices; both belong to the "Users" bounded context per `docs/DOMAIN_MAP.md`):

- **Transport (4 controllers + 6 FormRequests):**
  - `AuthController` (5 methods: register/login/logout/me/updateProfile; constructor-depends on 5 handlers; not `final`).
  - `PasswordController` (forgot/reset; not `final`).
  - `VerificationController` (verify/resend; not `final`).
  - `AccountOrdersController` (index/legacyIndex/show/summary; already `final`).
  - `LoginRequest`, `RegisterRequest` (final), `ForgotPasswordRequest`, `ResetPasswordRequest` (final), `UpdateProfileRequest`, `AccountOrderIndexRequest` — 4 not final.
- **Routes:** `routes/api.php:22-37,57-66` — all `/auth/*` (9 routes incl. `verification.verify`/`verification.send` named routes) + `/account/orders/*` (4 routes) + `/orders/me*` (2 legacy aliases). All paths, verbs, middleware (`throttle:auth.login`, `throttle:6,1`, `auth:sanctum`, `active.api.user`, `signed`), and route names stay byte-identical.
- **Application — Auth** (`app/Application/Auth/`): 8 command handlers + 1 query handler + 9 readonly command/query payloads + 7 DTOs + 3 contracts (`AuthUserRepository`, `AuthPasswordBrokerRepository`, `AuthAuditLogger`) + 5 support classes (`AuthAuditContext`, `AuthAuditContextResolver`, `AuthAuditEvent` enum, `AuthLoginRateLimitKey`, `AuthUserDtoMapper`) + 3 top-level classes (`AuthAccessTokenIssuer`, `AuthActiveUserRevalidator`, `AuthApplicationException`).
- **Application — Account Orders** (`app/Application/Account/Orders/`): 4 query handlers + 4 readonly query payloads + 11 DTOs + 1 contract (`AccountOrderReadRepository`) + 1 projector (`AccountOrderSummaryProjector`).
- **Repositories (3 files):** `AuthUserRepository` (stateless, has `DUMMY_PASSWORD_HASH` timing-safe constant), `AuthPasswordBrokerRepository` (constructor-depends on `AuthUserRepository`), `AccountOrderReadRepository` (uses shared trait `App\Repositories\Concerns\AppliesOrderSearch`).
- **Middleware (1):** `EnsureActiveApiUser` (constructor-depends on `AuthActiveUserRevalidator`; registered as alias `'active.api.user'` in `bootstrap/app.php:29`; behaviorally locked as global revalidation on every `auth:sanctum` route).
- **Infrastructure (1):** `app/Infrastructure/Auth/ObservabilityAuthAuditLogger.php` (implements `AuthAuditLogger`; bound by `AuthBindingsServiceProvider`).
- **ServiceProvider:** `app/Providers/AuthBindingsServiceProvider.php` — owns 3 Auth contract bindings + `auth.login` rate limiter (`RateLimiter::for(...)`) + `Password::defaults(min(12)->letters()->numbers())` boot. Moves wholesale.
- **Shared trait:** `app/Http/Controllers/Concerns/ResolvesAuthenticatedUser.php` — used by 3 moving controllers **AND** by `CartController`/`CheckoutController` (out-of-slice). Stays in legacy bridge per C0.
- **DI bindings to relocate:** `AuthBindingsServiceProvider` → moves wholesale (becomes `UsersServiceProvider`); `ApplicationBindingsServiceProvider:34` `AccountOrderReadRepository` binding → moves into `UsersServiceProvider`.
- **Configuration stays global:** `config/auth.php`, `config/sanctum.php` — consumed by the moving provider but not module-internal. Sanctum token expiration, login throttle config, password broker config — all stay.
- **Tests that move (6 feature + 9 unit + 2 support):** `AuthFlowTest`, `ProfileUpdateTest`, `PasswordResetFlowTest`, `EmailVerificationTest`, `AuthAuditTrailFeatureTest`, `AccountOrdersApiTest`; 9 unit tests under `tests/Unit/Application/Auth/` + `tests/Unit/Account/AccountOrderSummaryProjectorTest.php`; `tests/Support/Auth/{AuthUserRepositoryFake,AuthAuditLoggerSpy}.php`.
- **Frontend:** untouched. API paths (`/api/v1/auth/*`, `/api/v1/account/orders/*`) locked by `docs/api/openapi.yaml` (S1); frontend has zero FQCN coupling.
- **Eloquent model imports:** `User`, `Order`, `OrderItem`, `Payment`, `Shipment` (all stay in `App\Models\*` per C0/C1; model-ownership wave is post-C7).
- **Smoke/admin coupling:** zero direct class coupling; zero smoke DTO coupling (unlike C1 — strict subset).
- **Cross-module contract surface needed post-move:** none. The 4 existing contracts move into `app/Domains/Users/Contracts/` for module-internal use; no other module consumes them.

## Resolved design decisions (`2026-07-05`)

1. **Module name: `Users`.** Covers the Auth + Account bounded context per `docs/DOMAIN_MAP.md`. The placeholder `app/Domains/Users/README.md` confirms this name.
2. **Subfolder structure:** `Controllers`, `Application/{Commands,Queries,Dto}`, `Contracts`, `Middleware`, `Repositories`, `Infrastructure`, `Support`. No `Services/` (none exist today).
3. **ServiceProvider: `UsersServiceProvider`.** `AuthBindingsServiceProvider` is renamed to `UsersServiceProvider` and physically moved into `app/Domains/Users/UsersServiceProvider.php`. It absorbs the `AccountOrderReadRepository` binding from `ApplicationBindingsServiceProvider` (precedent: C1's `CatalogServiceProvider` absorbed the catalog repository binding).
4. **Middleware: moves into `app/Domains/Users/Middleware/EnsureActiveApiUser.php`.** Constructor-depends on `AuthActiveUserRevalidator` which moves with the slice; alias registration in `bootstrap/app.php` updates atomically to the new FQCN. The alias string `'active.api.user'` is stable (it's the wire surface).
5. **Infrastructure logger: moves into `app/Domains/Users/Infrastructure/ObservabilityAuthAuditLogger.php`.** Module-internal; bound by `UsersServiceProvider`.
6. **Trait `ResolvesAuthenticatedUser`: stays at `app/Http/Controllers/Concerns/ResolvesAuthenticatedUser.php`.** Used cross-module by `CartController`/`CheckoutController` (C3/C4 waves). Stays as a legacy-bridge concern per C0 §3; the moving controllers import it via the `App\Http\*` allowlist.
7. **Trait `AppliesOrderSearch`: stays at `app/Repositories/Concerns/AppliesOrderSearch.php`.** May be used by other read repositories; stays as a shared concern under the `App\Repositories\*` legacy-bridge allowance. `AccountOrderReadRepository` (after the move to `App\Domains\Users\Repositories\`) imports it cross-module via the allowlist.
8. **`final` on move (AGENTS.md default):** `AuthController`, `PasswordController`, `VerificationController` → `final`; `LoginRequest`, `ForgotPasswordRequest`, `UpdateProfileRequest`, `AccountOrderIndexRequest` → `final`. (Already-final classes keep `final`: `AccountOrdersController`, `RegisterRequest`, `ResetPasswordRequest`.)
9. **No new contracts.** The 4 existing contracts move into `app/Domains/Users/Contracts/`. They are not consumed by other modules — verified by exhaustive grep. This is a strict subset of C1's contract surface (C1 needed `CatalogReadService` + `Dto/CatalogProductListFilterDto` for smoke consumers).
10. **Stable operational contracts preserved:**
    - `AuthAuditEvent` enum literal values (`login.succeeded`, `login.failed`, `logout`, `token.issued`, `token.revoked`, `password.reset.requested`, `password.reset.completed`, `email.verified`) — telemetry consumers depend on these strings.
    - Route names `verification.verify` and `verification.send` — used by `URL::temporarySignedRoute()` in tests and notifications.
    - `DUMMY_PASSWORD_HASH` constant in `AuthUserRepository` — timing-attack mitigation locked by `AuthUserRepositoryPasswordVerificationTest`.
    - Sanctum token expiration, login throttle config, password policy — all stay in global config.
    - Email verification hash algorithm (`sha1`).
11. **`AuthApplicationException` moves into `app/Domains/Users/Application/AuthApplicationException.php`.** Module-internal exception type; rendered by the shared `App\Support\Api\ApiExceptionRenderer` (legacy bridge) — the renderer is allowlisted and the exception class is referenced only by moving handlers.
12. **`UsersServiceProvider` registered in `bootstrap/providers.php`** replacing `AuthBindingsServiceProvider`. The list now contains both `CatalogServiceProvider` (C1) and `UsersServiceProvider` (C2). The `InfrastructureProviderBoundaryTest` provider-list assertion updates atomically.

## Invariants to preserve

1. **Wire contract byte-identical.** All `/api/v1/auth/*` and `/api/v1/account/orders/*` endpoints: paths, verbs, request schemas, response schemas, status codes, throttle (`auth.login`, `6,1`), middleware (`auth:sanctum`, `active.api.user`, `signed`). Verified by `OpenApiConformanceFeatureTest` (S1).
2. **Route names `verification.verify` and `verification.send` unchanged.** Used by signed-URL generation and notifications.
3. **`AuthAuditEvent` literal values unchanged.** Telemetry contract.
4. **`active.api.user` middleware alias unchanged.** Behaviorally locked as global revalidation on every authenticated route.
5. **Sanctum token expiration, login throttle, password policy unchanged.** Global config untouched.
6. **Timing-safe missing-user password verification unchanged.** `DUMMY_PASSWORD_HASH` and the verify-then-revoke flow stay.
7. **Frontend untouched.** Zero FQCN coupling.
8. **Eloquent models stay shared.** `User`, `Order`, `OrderItem`, `Payment`, `Shipment` remain in `app/Models/*`; the relocated code imports them under the `App\Models\` legacy-bridge allowance.
9. **Guardrail extended, not weakened.** `ModuleBoundaryGuardrailTest` continues to pass; the migration proves C0/C1 boundaries hold for a second module. Legacy-bridge allowlist effectively shrinks (Auth + Account namespaces no longer in legacy paths).

## Out of scope (deliberate)

- **Cart/Checkout slices.** They migrate in C3/C4. The shared trait `ResolvesAuthenticatedUser` stays in legacy bridge until then.
- **Admin slice.** Zero direct coupling today (verified).
- **Eloquent model ownership.** Models stay in `app/Models/*`. Model-ownership wave is post-C7.
- **Global auth config.** `config/auth.php`, `config/sanctum.php` stay.
- **Reshaping contracts to return DTOs instead of `User`/`Order`.** Documented allowance; revisit at the model-ownership wave.
- **Frontend changes.** Zero.

## Implementation slices

### Slice 1 — Move implementation files (git mv for history)

All moves atomic; namespace + use-statement updates land in Slice 2.

Create module subfolders:

- `app/Domains/Users/{Controllers,Application/Commands,Application/Queries,Application/Dto,Contracts,Middleware,Repositories,Infrastructure,Support}`.

Move via `git mv`:

- **Controllers (4):** `app/Http/Controllers/Api/V1/Auth/{AuthController,PasswordController,VerificationController}.php` → `app/Domains/Users/Controllers/`; `app/Http/Controllers/Api/V1/Account/AccountOrdersController.php` → `app/Domains/Users/Controllers/`.
- **FormRequests (6):** `app/Http/Requests/Auth/{Login,Register,ForgotPassword,ResetPassword,UpdateProfile}Request.php` + `app/Http/Requests/Account/AccountOrderIndexRequest.php` → `app/Domains/Users/Controllers/` (precedent: C1 put `CatalogIndexRequest` under `Controllers/`).
- **Application Auth:** `app/Application/Auth/Commands/*` (8 handlers + 8 payloads) → `app/Domains/Users/Application/Commands/`; `app/Application/Auth/Queries/*` (1 handler + 1 payload) → `app/Domains/Users/Application/Queries/`; `app/Application/Auth/Dto/*` (7 DTOs) → `app/Domains/Users/Application/Dto/`; `app/Application/Auth/Contracts/*` (3 interfaces) → `app/Domains/Users/Contracts/`; `app/Application/Auth/Support/*` (5 classes) → `app/Domains/Users/Support/`; `app/Application/Auth/{AuthAccessTokenIssuer,AuthActiveUserRevalidator,AuthApplicationException}.php` → `app/Domains/Users/Application/`.
- **Application Account Orders:** `app/Application/Account/Orders/Queries/*` (4 handlers + 4 payloads) → `app/Domains/Users/Application/Queries/`; `app/Application/Account/Orders/Dto/*` (11 DTOs) → `app/Domains/Users/Application/Dto/`; `app/Application/Account/Orders/Contracts/AccountOrderReadRepository.php` → `app/Domains/Users/Contracts/`; `app/Application/Account/Orders/Support/AccountOrderSummaryProjector.php` → `app/Domains/Users/Support/`.
- **Repositories (3):** `app/Repositories/{AuthUserRepository,AuthPasswordBrokerRepository,AccountOrderReadRepository}.php` → `app/Domains/Users/Repositories/`.
- **Middleware (1):** `app/Http/Middleware/EnsureActiveApiUser.php` → `app/Domains/Users/Middleware/`.
- **Infrastructure (1):** `app/Infrastructure/Auth/ObservabilityAuthAuditLogger.php` → `app/Domains/Users/Infrastructure/`.
- **ServiceProvider:** `app/Providers/AuthBindingsServiceProvider.php` → `app/Domains/Users/UsersServiceProvider.php` (renamed class).
- **Tests that move (6 feature + 9 unit + 2 support):** keep the relative structure under `tests/` — feature tests stay under `tests/Feature/`; unit tests move into `tests/Unit/Application/Users/` (mirroring C1's `tests/Unit/CatalogVersionServiceTest.php` namespace pattern); support files stay at `tests/Support/Auth/`.

**Trait `ResolvesAuthenticatedUser` does NOT move** (stays at `app/Http/Controllers/Concerns/ResolvesAuthenticatedUser.php` — shared with Cart/Checkout). **Trait `AppliesOrderSearch` does NOT move** (stays at `app/Repositories/Concerns/AppliesOrderSearch.php`).

### Slice 2 — Namespace + use-statement updates

For every moved file, update:

1. `namespace` declaration to the new `App\Domains\Users\*` path.
2. Every `use` statement referencing the moved classes (within-slice references update to new namespaces; out-of-slice references like `App\Models\*`, `App\Http\Controllers\Concerns\ResolvesAuthenticatedUser`, `App\Repositories\Concerns\AppliesOrderSearch`, `App\Contracts\CartServiceInterface`, `App\Support\*`, `App\Enums\*`, `App\Infrastructure\*` (for non-moving infra) stay as legacy-bridge imports).
3. Add `final` to 3 controllers + 4 FormRequests per decision 8.

### Slice 3 — Wiring: routes, providers, middleware alias, psalm.xml

- `routes/api.php` — update 7 controller imports to the new namespace.
- `bootstrap/app.php:7` — update `use App\Http\Middleware\EnsureActiveApiUser;` to `use App\Domains\Users\Middleware\EnsureActiveApiUser;`.
- `bootstrap/providers.php` — replace `App\Providers\AuthBindingsServiceProvider::class` with `App\Domains\Users\UsersServiceProvider::class`.
- `UsersServiceProvider` — rename class; update imports; add `AccountOrderReadRepository` binding (absorbed from `ApplicationBindingsServiceProvider`).
- `ApplicationBindingsServiceProvider` — remove `AccountOrderReadRepository` contract + implementation imports and the binding line (3 changes).
- `psalm.xml` — any per-file suppression paths update; verify the moving repository directory needs to be added to `TooManyTemplateParams` + `InvalidDocblock` suppressions (same as C1's catalog repositories).

### Slice 4 — Guardrail test updates

Update namespace literals, paths, and imports in:

- `tests/Unit/Architecture/ApplicationAuthRepositoryBoundaryTest.php` — handler/contract FQCNs.
- `tests/Unit/Architecture/AuthAuditEmissionGuardrailTest.php` — imports + literal FQCN for `ObservabilityAuthAuditLogger`.
- `tests/Unit/Architecture/AuthCredentialHardeningGuardrailTest.php` — FormRequest imports.
- `tests/Unit/Architecture/AuthTokenLifecycleGuardrailTest.php` — contract import.
- `tests/Unit/Architecture/SecurityConfigGuardrailTest.php:147` — hardcoded `'App\Http\Middleware\EnsureActiveApiUser'` literal.
- `tests/Unit/Architecture/InfrastructureProviderBoundaryTest.php` — provider-list assertion (replace `AuthBindingsServiceProvider` with `UsersServiceProvider`).
- `tests/Unit/Architecture/RepositoryReadBoundaryTest.php:29` — `AccountOrderReadRepository` path.
- `tests/Unit/Architecture/RepositoryBusinessDecisionBoundaryTest.php` — import + path for `AccountOrderReadRepository`.
- `tests/Unit/Architecture/LegacyPayloadArtifactGuardrailTest.php` — directory paths.
- `tests/Unit/Architecture/ModuleBoundaryGuardrailTest.php` — verify it passes trivially (no outbound `Domains\*` imports from Users).

### Slice 5 — Tests that move

Update namespaces + imports in the 6 feature + 9 unit + 2 support test files to reference the new module namespaces. Add a new `tests/Feature/UsersModuleRelocationTest.php` (precedent: C1's `CatalogModuleRelocationTest`) — locks the contract bindings (4 contracts → implementations) and verifies routes resolve to the new controller namespace.

### Slice 6 — Module README + skeleton guardrail verification

- `app/Domains/Users/README.md` — replace the placeholder with the active module documentation following C1's template: active subfolders, public contract surface (the 4 contracts + their purposes), operational contracts (route names, audit event literals, sanctum invariants, password policy, DUMMY_PASSWORD_HASH timing-safety, `active.api.user` alias), migration state.
- `ModularMonolithSkeletonGuardrailTest` — confirm the README heading assertion (`# Users Domain Module`) still passes.
- `ModuleBoundaryGuardrailTest` — confirm green.

### Slice 7 — Docs sync

- `docs/REPO_MAP.md` — update the Users row in the per-module ownership table: migration state `complete (C2)`; list the actual contract surface.
- `docs/DOMAIN_MAP.md` — update the Account/Auth/Admin migration-state markers to `[migration: complete C2]` (for the Auth + Account parts); cross-reference the new module location.
- `docs/ARCHITECTURE_REFACTOR_NEXT.md` — mark `C2` closed; append closed-block definition; update exit target #26 (now fully achieved — both Catalog C1 and Users C2); append change-control entry.
- `docs/REFACTORING_EXECUTION_PLAN.md` — append full C2 entry with verified baseline, slices, invariants, deterministic coverage, and verification steps.

### Slice 8 — Quality gate

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

Plus the route-smoke requirement (controllers/middleware changed): `php artisan optimize:clear` and `php artisan route:list --path=api/v1/auth` + `php artisan route:list --path=api/v1/account`.

## Definition of done

1. Users slice (transport + application + repositories + middleware + infrastructure + provider) lives under `app/Domains/Users/*` with namespace moves; old files removed.
2. `UsersServiceProvider` registered in `bootstrap/providers.php`; binds the 4 contracts (3 Auth + 1 Account) and owns `auth.login` rate limiter + `Password::defaults()`.
3. `ApplicationBindingsServiceProvider` no longer binds `AccountOrderReadRepository`.
4. `active.api.user` middleware alias resolves to the new `EnsureActiveApiUser` FQCN.
5. `/api/v1/auth/*` and `/api/v1/account/orders/*` wire contract byte-identical (`OpenApiConformanceFeatureTest` green); route names `verification.verify`/`verification.send` unchanged.
6. `AuthAuditEvent` literal values unchanged; `DUMMY_PASSWORD_HASH` unchanged; sanctum/throttle/password-policy config unchanged.
7. `ModuleBoundaryGuardrailTest` green (legacy-bridge allowlist effectively shrinks further).
8. Users README documents the active contract surface; skeleton guardrail still passes.
9. `docs/ARCHITECTURE_REFACTOR_NEXT.md` records `C2` as closed; exit target #26 fully achieved; `docs/REFACTORING_EXECUTION_PLAN.md` records the work and checks.
10. The quality gate is green and the executed checks are reported.
