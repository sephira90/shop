# Repo Map

## Project type

Laravel ecommerce modular monolith with Vue SPA frontend.

Backend:

- Laravel 12
- PHP 8.4
- MySQL 8.4
- Redis 7

Frontend:

- Vue 3
- TypeScript
- Vite

## Architecture

- Canonical architecture contract: `docs/ARCHITECTURE.md`
- Active execution roadmap: `docs/ARCHITECTURE_REFACTOR_NEXT.md`
- Execution log: `docs/REFACTORING_EXECUTION_PLAN.md`

The application is a modularized monolith with explicit bounded contexts in `app/Application/*`.

## Domain map

See `docs/DOMAIN_MAP.md` for domain boundaries and dependencies.

## Target layout

Physical convergence target:

```text
app/
  Domains/
    Catalog/
    Cart/
    Checkout/
    Orders/
    Users/
    Payments/
    Webhooks/
```

The migration is incremental and must keep public API and DB contracts stable. The module-boundary contract that governs `app/Domains/*` is defined in `docs/ARCHITECTURE.md` → Module Boundary Contract (C0). Each module exposes `app/Domains/<Module>/Contracts/` as its public API; cross-module imports go through Contracts only.

### Per-module ownership and migration state

| Module | Public API (Contracts surface) | Owning wave | Migration state |
| --- | --- | --- | --- |
| `Catalog` | `CatalogProductReadRepository`, `CatalogCacheVersion`, `CatalogReadService`, `Dto/CatalogProductListFilterDto` | `C1` | complete (`2026-07-05`) |
| `Users` | `AuthUserRepository`, `AuthPasswordBrokerRepository`, `AuthAuditLogger`, `AccountOrderReadRepository` | `C2` | complete (`2026-07-05`) |
| `Cart` | cart resolver/mutation contracts, `CartPolicy` | `C3` | pending |
| `Checkout` | checkout place-order contract, idempotency guard | `C4` | pending |
| `Orders` | order transition policies, `OrderInventoryReleaseService`, stale-aggregate failure contract | `C5` | pending |
| `Payments` | gateway contracts (`PaymentGatewayInterface`), payment transition policy | `C6` | pending |
| `Webhooks` | `WebhookProcessorAdapterInterface`, ingress resolvers/appliers, `Process*WebhookJob` | `C7` | pending |

Legacy bridge namespaces importable from any module during the migration are listed in `docs/ARCHITECTURE.md` → Module Boundary Contract → Always-allowed namespaces and locked by `tests/Unit/Architecture/ModuleBoundaryGuardrailTest.php`. The allowlist only shrinks as modules relocate.

## Current state snapshot (`2026-03-05`)

- Runtime bounded contexts are implemented primarily in `app/Application/*` (`Account`, `Admin`, `Auth`, `Catalog`, `Cart`, `Checkout`, `Webhook`).
- Business orchestration and transition policies remain in `app/Services/*` with shared domain value objects/exceptions in `app/Domain/*`.
- `app/Domains/*` is present as a convergence skeleton (README contracts per domain module), with incremental migration still pending per flow.
- Frontend follows contract-first transport boundaries through:
  - `resources/js/contracts/api/v1/*`,
  - `resources/js/mappers/*`,
  - `resources/js/api/*`.
- Shared UI primitives are centralized in `resources/js/components/ui/*`; domain-specific UI remains in dedicated folders (`components/admin`, `components/cart`, `components/catalog`, etc.).
- Operational modules are implemented in `app/Support/Observability/*`, `app/Support/Smoke/*`, `app/Support/Maintenance/*` and exposed via `app/Console/Commands/*`.

## Backend structure

- `app/Http/Controllers/*` transport only
- `app/Http/Requests/*` validation boundary
- `app/Http/Middleware/*` global + alias middleware (correlation id, API telemetry, Force HTTPS, idempotency key, role, active API user)
- `app/Application/*` command/query handlers + DTO boundaries
- `app/Services/*` domain/business orchestration
- `app/Repositories/*` persistence/query boundaries
- `app/Models/*` ORM entities (privilege/state fields excluded from `$fillable`; transitions use `forceFill([...])->save()`)
- `app/Jobs/*`, `app/Events/*`, `app/Listeners/*` async and side effects

## Transport security baseline

- `config/cors.php` — env-driven allowlist (`CORS_ALLOWED_ORIGINS`), scoped to `api/*`, credentials disabled.
- `config/security.php` — `force_https` (env `APP_FORCE_HTTPS`, default `true`), `trusted_proxies`, `trusted_hosts`.
- `app/Http/Middleware/ForceHttpsMiddleware.php` — redirect-to-HTTPS in non-local env when force-https enabled; honors `X-Forwarded-Proto: https` to prevent proxy redirect loops; registered globally in `bootstrap/app.php`.
- `config/session.php` — secure-cookie default `env('SESSION_SECURE_COOKIE', env('APP_ENV', 'production') !== 'local')` (defaults secure in non-local without explicit env override).

## Data classification and payload boundaries

- `docs/SECURITY_DATA_CLASSIFICATION.md` — inventory of PII-bearing columns (`users.email/phone/password`, `orders.email/billing_address/shipping_address`, `payments.payload`, `shipments.payload`), allowed key sets per JSON column, plaintext-at-rest threat model, and field-level-encryption follow-up prerequisites. Enforced by `tests/Unit/Architecture/SecurityDataClassificationDocGuardrailTest.php`.
- `app/Application/Checkout/Dto/CheckoutAddressInputDto.php` — emits the closed address shape `{line1, city, country, postcode}`; the only sanctioned address constructor for persistence. Locked by `tests/Unit/Architecture/AddressPayloadBoundaryGuardrailTest.php` (scans all `billing_address`/`shipping_address` blob construction sites under `app/`).
- `app/Contracts/PaymentGatewayInterface.php` + `app/Contracts/ShippingGatewayInterface.php` — gateway contracts; concrete adapters (`app/Infrastructure/Payments/FakePaymentGateway.php`, `app/Infrastructure/Shipping/FakeShippingGateway.php`) build payloads through `App\Support\Data\JsonPayload::fromArray()` using provider-operational key sets only. Locked by `tests/Unit/Architecture/GatewayPayloadBoundaryGuardrailTest.php` (bans `card`/`card_number`/`pan`/`cvv`/`cvc`/`ssn`/`password`/`recipient_name` literals).

## API

Main API prefix: `/api/v1`

Endpoint groups:

- `/catalog`
- `/cart`
- `/checkout`
- `/account`
- `/orders/me` (legacy account-order aliases)
- `/admin`
- `/webhooks`

## AI entry points

### API behavior change

1. `routes/api.php`
2. `app/Http/Controllers/Api/V1/*`
3. `app/Http/Requests/*`
4. `app/Application/*/(Commands|Queries)/*Handler.php`
5. `app/Application/*/Dto/*`

### Checkout flow

- `app/Services/Checkout/CheckoutService.php`
- `app/Services/Checkout/CheckoutPlaceOrderOrchestrator.php`
- `app/Services/Checkout/*`
- `app/Services/Payment/*`
- `app/Services/Order/OrderStatusTransitionPolicy.php`

### Webhook flow

- `app/Http/Controllers/Api/V1/Webhook/*Controller.php`
- `app/Services/Webhook/WebhookProcessingPipeline.php`
- `app/Services/Payment/*Webhook*`
- `app/Services/Shipping/*Webhook*`
- `app/Jobs/Process*WebhookJob.php`

### Admin flow

- `app/Http/Controllers/Api/V1/Admin/*Controller.php`
- `app/Application/Admin/*`
- `app/Application/Admin/*/Contracts/*`
- `app/Repositories/Admin*Repository.php`

### Frontend flow

- `resources/js/pages/*`
- `resources/js/composables/*`
- `resources/js/composables/admin/*`
- `resources/js/contracts/api/v1/*`
- `resources/js/queries/*`
- `resources/js/validators/*`

## Implementation rules (agent-facing)

- Controllers stay thin (authorize/validate/delegate).
- Business rules live in services/policies/domain objects.
- Repositories are persistence-only.
- Keep `/api/v1/*` envelope backward-compatible.
- Add or update tests for every behavior change.
- Run sequential quality gate before completion.
