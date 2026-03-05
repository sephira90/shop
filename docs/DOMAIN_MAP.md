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
- Entry points:
  - `app/Application/Catalog/*`
  - `app/Repositories/CatalogProductReadRepository.php`
  - `resources/js/pages/CatalogPage.vue`

### Cart

- Cart read/mutation orchestration and ownership guards.
- Entry points:
  - `app/Application/Cart/*`
  - `app/Services/Cart/*`

### Checkout

- Place-order orchestration: identity, idempotency, inventory, discount, finalization.
- Entry points:
  - `app/Application/Checkout/*`
  - `app/Services/Checkout/*`

### Order lifecycle

- Status transition policies and admin status mutation behavior.
- Entry points:
  - `app/Services/Order/OrderStatusTransitionPolicy.php`
  - `app/Services/Admin/AdminOrderService.php`
  - `app/Services/Payment/PaymentStatusTransitionPolicy.php`
  - `app/Services/Shipping/ShipmentStatusTransitionPolicy.php`

### Webhook

- Ingress validation, receipt/idempotency, transition application, observability.
- Entry points:
  - `app/Services/Webhook/WebhookProcessingPipeline.php`
  - `app/Services/Payment/*Webhook*`
  - `app/Services/Shipping/*Webhook*`
  - `app/Jobs/Process*WebhookJob.php`

### Account/Auth/Admin

- Transport and use-case boundaries for authenticated and management APIs.
- Entry points:
  - `app/Http/Controllers/Api/V1/Account/*`
  - `app/Http/Controllers/Api/V1/Auth/*`
  - `app/Http/Controllers/Api/V1/Admin/*`
  - `app/Application/Account/*`
  - `app/Application/Auth/*`
  - `app/Application/Admin/*`

## Cross-context interaction rules

- Interactions across contexts must use explicit contracts/DTO boundaries.
- No direct transport coupling inside domain/service/repository layers.
- No repository-level business decisions or transition semantics.
- Side effects dependent on committed state must be after-commit safe.
