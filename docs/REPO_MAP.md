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

The migration is incremental and must keep public API and DB contracts stable.

## Backend structure

- `app/Http/Controllers/*` transport only
- `app/Http/Requests/*` validation boundary
- `app/Application/*` command/query handlers + DTO boundaries
- `app/Services/*` domain/business orchestration
- `app/Repositories/*` persistence/query boundaries
- `app/Models/*` ORM entities
- `app/Jobs/*`, `app/Events/*`, `app/Listeners/*` async and side effects

## API

Main API prefix: `/api/v1`

Endpoint groups:

- `/catalog`
- `/cart`
- `/checkout`
- `/account`
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
