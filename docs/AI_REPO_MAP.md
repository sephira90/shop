# AI Repo Map

## Purpose

This map is an execution entrypoint for coding agents. It reduces navigation ambiguity and keeps changes inside architecture boundaries.

Primary policy order:

1. `AGENTS.md`
2. `.cursorrules`
3. `docs/ARCHITECTURE.md`
4. `docs/ARCHITECTURE_REFACTOR_NEXT.md`

## Entry points by task

### API behavior change

- Routes: `routes/api.php`
- Controller: `app/Http/Controllers/Api/V1/*`
- Requests: `app/Http/Requests/*`
- Handler(s): `app/Application/*/(Commands|Queries)/*Handler.php`
- DTO contracts: `app/Application/*/Dto/*`
- Repository/service boundary: `app/Repositories/*`, `app/Services/*`
- Tests first:
  - `tests/Feature/*`
  - `tests/Unit/Architecture/*` (if boundary changed)

### Checkout/order flow

- Orchestration:
  - `app/Services/Checkout/CheckoutService.php`
  - `app/Services/Checkout/CheckoutPlaceOrderOrchestrator.php`
- Idempotency/inventory/discount:
  - `app/Services/Checkout/*`
- Payment integration:
  - `app/Services/Payment/*`
  - `app/Contracts/PaymentGatewayInterface.php`
- Status transitions:
  - `app/Services/Order/OrderStatusTransitionPolicy.php`
  - `app/Services/Payment/PaymentStatusTransitionPolicy.php`
  - `app/Services/Shipping/ShipmentStatusTransitionPolicy.php`

### Webhooks

- Controllers:
  - `app/Http/Controllers/Api/V1/Webhook/*Controller.php`
- Pipeline:
  - `app/Services/Webhook/WebhookProcessingPipeline.php`
- Provider-specific adapters/resolvers:
  - `app/Services/Payment/*Webhook*`
  - `app/Services/Shipping/*Webhook*`
- Idempotency identity:
  - `app/Jobs/Process*WebhookJob.php`
  - `app/Models/WebhookReceipt.php`

### Admin read/write flows

- Controllers:
  - `app/Http/Controllers/Api/V1/Admin/*Controller.php`
- Query handlers and contracts:
  - `app/Application/Admin/*`
- Read repositories:
  - `app/Repositories/Admin*Repository.php`
  - contracts in `app/Application/Admin/*/Contracts/*`

### Frontend flow

- Pages (orchestration only): `resources/js/pages/*`
- Composables:
  - `resources/js/composables/*`
  - `resources/js/composables/admin/*`
- Transport contracts/assertions:
  - `resources/js/contracts/api/v1/*`
  - `resources/js/contracts/api/v1/assertions/*`
- Query normalization:
  - `resources/js/queries/*`
  - `resources/js/validators/*`

## Bounded context map

- `Admin`: management APIs and read models
- `Catalog`: public listing and product detail
- `Cart`: mutation and retrieval boundaries
- `Checkout`: place order + idempotency + stock/discount orchestration
- `Account`: authenticated user order/profile flows
- `Auth`: registration/login/password/verification
- `Webhook`: payment/shipping ingress + transitions + observability

## Dependency map (quick)

- Controllers -> Application Handlers
- Handlers -> Contracts/DTO/Services/Repositories contracts
- Services -> Domain + Infrastructure contracts + repositories
- Repositories -> DB/Eloquent only

Never invert this direction.

## Change workflow for agents

1. Locate target flow from this map.
2. Validate boundary before editing (`tests/Unit/Architecture/*`).
3. Implement one coherent architectural slice.
4. Update/add tests at the correct level.
5. Run full sequential quality gate.
6. Update `docs/REFACTORING_EXECUTION_PLAN.md`.

## Guardrail index

Primary architecture guardrails live in:

- `tests/Unit/Architecture/*`

Start with:

- controller dependency boundaries
- application handler/DTO boundaries
- repository business-decision boundaries
- queue/listener safety and after-commit boundaries
- policy completeness matrix
