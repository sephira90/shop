# Users Domain Module

The Users module owns authentication, profile management, password reset,
email verification, and the account orders read surface. It also owns the
`active.api.user` middleware alias used by every authenticated route in the
application.

## Active subfolders

- `Contracts/` — module public API (4 interfaces); see below.
- `Controllers/` — transport: 4 controllers (`AuthController`, `PasswordController`, `VerificationController`, `AccountOrdersController`) + 6 FormRequests.
- `Application/` — `Commands/` (8 handlers + 8 payloads), `Queries/` (5 handlers + 5 payloads), `Dto/` (7 Auth + 11 Account DTOs), `AuthAccessTokenIssuer`, `AuthActiveUserRevalidator`, `AuthApplicationException`.
- `Support/` — `AuthAuditContext`, `AuthAuditContextResolver`, `AuthAuditEvent` enum, `AuthLoginRateLimitKey`, `AuthUserDtoMapper`, `AccountOrderSummaryProjector`.
- `Repositories/` — `AuthUserRepository`, `AuthPasswordBrokerRepository`, `AccountOrderReadRepository`.
- `Middleware/` — `EnsureActiveApiUser` (registered globally as alias `active.api.user`).
- `Infrastructure/` — `ObservabilityAuthAuditLogger` (bound to `AuthAuditLogger`).
- `UsersServiceProvider.php` — binds the 4 contracts; owns the `auth.login` rate limiter and `Password::defaults()` boot; registered in `bootstrap/providers.php`.

The `Models/` subfolder is deferred to the post-C7 model-ownership wave.
Eloquent models (`User`, `Order`, `OrderItem`, `Payment`, `Shipment`) stay
shared in `app/Models/*` and are imported by this module under the C0
legacy-bridge allowance.

## Public contract surface

`App\Domains\Users\Contracts\`

- `AuthUserRepository` — user CRUD + token + role + verification + password operations. Returns `User` at the boundary as a documented shared-kernel allowance pending the model-ownership wave.
- `AuthPasswordBrokerRepository` — password reset broker wrapper (`sendResetLink`, `resetPassword`, status predicates).
- `AuthAuditLogger` — auth telemetry contract (`log(AuthAuditEvent, AuthAuditContext)`); bound to the observability implementation.
- `AccountOrderReadRepository` — paginated order history for the authenticated user. Returns `Order` at the boundary as the same shared-kernel allowance.

No contract is consumed by other modules. The Users module is the first
runtime module (after C1) that exposes a contract surface purely for
module-internal use; the contracts document the boundary but no other
module imports them today.

## Operational contracts

- `AuthAuditEvent` enum literal values (`login.succeeded`, `login.failed`, `logout`, `token.issued`, `token.revoked`, `password.reset.requested`, `password.reset.completed`, `email.verified`) are a machine-readable observability contract; renaming breaks telemetry consumers.
- Route names `verification.verify` and `verification.send` are used by signed-URL generation (`URL::temporarySignedRoute`) and notifications; must not change.
- `active.api.user` middleware alias is the stable surface referenced by every authenticated route; the FQCN behind it lives in this module.
- Sanctum token expiration (`SANCTUM_TOKEN_EXPIRATION_MINUTES`, default 1440), `auth.login` throttle (`auth.login_throttle.{max_attempts,decay_seconds}`), and `Password::defaults()` (`min(12)->letters()->numbers()`) are stable operational contracts; the config lives globally in `config/auth.php` and `config/sanctum.php` and is consumed by `UsersServiceProvider::boot()`.
- `AuthUserRepository::DUMMY_PASSWORD_HASH` is a timing-attack mitigation for missing-user login attempts; locked by `AuthUserRepositoryPasswordVerificationTest`.
- The `/api/v1/auth/*` and `/api/v1/account/orders/*` HTTP contracts are locked by `docs/api/openapi.yaml` (S1) and verified by `tests/Feature/OpenApiConformanceFeatureTest.php`.
- Relocation wired by `tests/Feature/UsersModuleRelocationTest.php`.

## Migration state

Complete (Wave C2, `2026-07-05`). The Auth + Account Orders bounded contexts
moved into this module as a single slice. The shared trait
`App\Http\Controllers\Concerns\ResolvesAuthenticatedUser` stays in legacy
bridge until the Cart/Checkout waves (C3/C4) consolidate it. The shared
trait `App\Repositories\Concerns\AppliesOrderSearch` stays as a shared
concern under the `App\Repositories\*` legacy-bridge allowance.
