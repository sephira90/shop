# Domain Map

## Bounded contexts

- `Admin`
- `Catalog`
- `Cart`
- `Checkout`
- `Account`
- `Auth`
- `Webhook` (payment + shipping ingress)
- `Order/Payment/Shipment` transition policies (cross-cutting domain rules)

Target modular-monolith module paths:

- `app/Domains/Catalog`
- `app/Domains/Cart`
- `app/Domains/Checkout`
- `app/Domains/Orders`
- `app/Domains/Users`
- `app/Domains/Payments`
- `app/Domains/Webhooks`

## Module Boundary Contract

The C0 foundation wave formalizes the contract surface every module must expose. Each module's public API lives in `app/Domains/<Module>/Contracts/` (interfaces + DTOs; no Eloquent models at the boundary). Cross-module imports go through Contracts only; the shared domain kernel (`App\Domain\*`) and legacy bridge namespaces are always importable during the migration.

Authoritative definition: `docs/ARCHITECTURE.md` → Module Boundary Contract.
Mechanical enforcement: `tests/Unit/Architecture/ModuleBoundaryGuardrailTest.php`.

## Current runtime module status (`2026-03-05`)

- Active runtime ownership currently lives in `app/Application/*`, `app/Services/*`, `app/Repositories/*`, and `app/Http/*`.
- `app/Domains/*` directories currently provide the target module skeleton and migration intent (README-level contracts), not full runtime slices yet.
- Account order reads are already extracted from checkout transport into:
  - `app/Http/Controllers/Api/V1/Account/AccountOrdersController.php`,
  - `app/Application/Account/Orders/*`,
  - `app/Repositories/AccountOrderReadRepository.php`.
- Webhook ingress is implemented as:
  - transport -> enqueue handler (`app/Application/Webhook/Commands/*`),
  - queued processing (`app/Jobs/Process*WebhookJob.php`),
  - unified pipeline (`app/Services/Webhook/WebhookProcessingPipeline.php`).

## Domain dependencies

Primary business flow dependency direction:

`Catalog -> Cart -> Checkout -> Order lifecycle`

Operational integration around order lifecycle:

- `Order lifecycle -> Payments`
- `Order lifecycle -> Shipping`
- `Payments/Shipping -> Webhook ingress -> Order lifecycle transitions`

## Context ownership

### Catalog

- Public product/category read models.
- Migration state: `[migration: complete C1]` → `app/Domains/Catalog` (`2026-07-05`).
- Module contracts: `App\Domains\Catalog\Contracts\{CatalogProductReadRepository, CatalogCacheVersion, CatalogReadService, Dto/CatalogProductListFilterDto}`. Admin writes consume `CatalogCacheVersion` for cache invalidation.
- Entry points (post-C1):
  - `app/Domains/Catalog/Controllers/CatalogController.php`
  - `app/Domains/Catalog/Application/Queries/*Handler.php`
  - `app/Domains/Catalog/Services/{CatalogService,CatalogVersionService}.php`
  - `app/Domains/Catalog/Repositories/CatalogProductReadRepository.php`
  - `resources/js/pages/CatalogPage.vue` (frontend untouched)

### Cart

- Cart read/mutation orchestration and ownership guards.
- Migration state: `[migration: complete C3]` → `app/Domains/Cart`. Module contracts: `CartServiceInterface` (cross-module — consumed by Users `LoginAuthUserHandler`, Checkout orchestrator, smoke scenarios), `CartMutationServiceInterface` (module-internal). First module to publish a contract surface with established cross-module consumers; Users → Cart import is the first non-trivial `Domains\<Other>\Contracts\` cross-module path exercised by `ModuleBoundaryGuardrailTest`. `CartPolicy` registered via `Gate::policy()` in `CartServiceProvider::boot()`. Eloquent models (`Cart`, `CartItem`), `CartStatus` enum, and `CartException` stay shared under legacy-bridge allowances pending the model-ownership wave.
- Entry points:
  - `app/Domains/Cart/Controllers/*`
  - `app/Domains/Cart/Application/*`
  - `app/Domains/Cart/Services/*`

### Checkout

- Place-order orchestration: identity, idempotency, inventory, discount, finalization.
- Migration state: `[migration: complete C4]` → `app/Domains/Checkout`. Module contracts: `CheckoutServiceInterface` (cross-module — consumed by smoke scenarios + intra-module orchestrator), `CheckoutShippingCostResolver` (module-internal extension point; default `FreeCheckoutShippingCostResolver`). Second module in the convergence waves to publish a contract surface with established cross-module consumers. Cart → Checkout coupling is one-way (Checkout imports `CartServiceInterface` cross-module; Cart does not import from Checkout). `RateLimiter::for('checkout')` and `EnsureIdempotencyKeyMiddleware` (alias `idempotency.key`) moved with the module. Eloquent models (`Order`, `OrderItem`, `Payment`, `Shipment`, `CheckoutIdempotency`), enums (`OrderStatus`/`PaymentStatus`/`ShipmentStatus`), `OrderPlaced` event, and `CheckoutException` stay shared under legacy-bridge allowances pending the model-ownership wave.
- Entry points:
  - `app/Domains/Checkout/Controllers/*`
  - `app/Domains/Checkout/Application/*`
  - `app/Domains/Checkout/Services/*`
  - `app/Domains/Checkout/Middleware/EnsureIdempotencyKeyMiddleware.php`

### Order lifecycle

- Status transition policies and admin status mutation behavior.
- Migration state: `[migration: pending C5]` → `app/Domains/Orders`.
- Entry points:
  - `app/Services/Order/OrderStatusTransitionPolicy.php`
  - `app/Services/Admin/AdminOrderService.php`
  - `app/Services/Payment/PaymentStatusTransitionPolicy.php`
  - `app/Services/Shipping/ShipmentStatusTransitionPolicy.php`

### Webhook

- Ingress validation, receipt/idempotency, transition application, observability.
- Migration state: `[migration: pending C7]` → `app/Domains/Webhooks`.
- Entry points:
  - `app/Services/Webhook/WebhookProcessingPipeline.php`
  - `app/Services/Payment/*Webhook*`
  - `app/Services/Shipping/*Webhook*`
  - `app/Jobs/Process*WebhookJob.php`

### Account/Auth/Admin

- Transport and use-case boundaries for authenticated and management APIs.
- Migration state:
  - Auth/Users: `[migration: complete C2]` → `app/Domains/Users` — module owns the Auth + Account bounded context. Module contracts: `AuthUserRepository`, `AuthPasswordBrokerRepository`, `AuthAuditLogger`, `AccountOrderReadRepository` (all module-internal; no other module imports them today).
  - Account order reads: `[migration: complete C2 (Users module)]` → `app/Domains/Users` (account reads landed in Users with the Auth surface; future Orders module wave will revisit read-model ownership alongside the order lifecycle module).
  - Admin contexts (catalog/categories/orders/products/promotions): split across `Catalog` (C1), `Orders` (C5), plus admin-specific contract surfaces defined per wave.
- Entry points:
  - `app/Domains/Users/Controllers/*` (auth + account orders)
  - `app/Http/Controllers/Api/V1/Admin/*`
  - `app/Domains/Users/Application/*`
  - `app/Application/Admin/*`

### Payments

- Payment gateway contracts and payment transition policy.
- Migration state: `[migration: pending C6]` → `app/Domains/Payments`.
- Entry points:
  - `app/Services/Payment/PaymentService.php`
  - `app/Contracts/PaymentGatewayInterface.php`
  - `app/Services/Payment/PaymentStatusTransitionPolicy.php`

## Cross-context interaction rules

- Interactions across contexts must use explicit contracts/DTO boundaries.
- No direct transport coupling inside domain/service/repository layers.
- No repository-level business decisions or transition semantics.
- Side effects dependent on committed state must be after-commit safe.
