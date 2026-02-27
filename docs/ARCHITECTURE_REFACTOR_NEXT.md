# Architecture Refactor Next (Architecture-First)

Date: `2026-02-27`
Status: `Active`
Priority mode: `Architecture-first`

## Execution Authority

1. This file is the active architecture execution source-of-truth.
2. Historical plans in `docs/*PLAN*.md` are archival references only and must not be used as active execution authority.
3. `docs/REFACTORING_EXECUTION_PLAN.md` remains an operational execution log only.

## Summary

DTO migration is completed, but architecture still has critical structural gaps:

- high business-logic concentration in large services,
- application layer still leaks ORM/paginator types in handlers,
- incomplete architectural guardrails against regressions.

Goal of this program: close those gaps without breaking `/api/v1/*` response envelope (`data/meta/error`) and with strict quality-gate enforcement after each logical block.

## Execution Progress

1. Wave 0 completed (governance source-of-truth reset).
2. Wave 1 completed (transport purity + full API V1 controller architecture coverage).
3. Wave 2 completed (webhook contract hardening, parity tests, ingress prevalidation + error taxonomy).
4. Wave 3 completed (filter DTO migration, enum status input contract, scalar-safe `toArray()` boundaries, legacy dead-code guardrails).
5. Wave 4 completed (service decomposition):
   - completed sub-blocks:
     - checkout discount resolution extracted to `CheckoutDiscountResolver`;
     - checkout idempotency decision path extracted to `CheckoutIdempotencyGuard`.
     - checkout inventory allocation extracted to `CheckoutInventoryAllocator`.
     - checkout cart preparation/order write extracted to `CheckoutCartPreparer` + `CheckoutOrderWriter`.
     - cart decomposition completed via explicit boundaries:
       - `CartResolver`,
       - `CartMutationService`,
       - `CartResultMapper`,
       with `CartService` reduced to facade orchestration.
     - status transition policies extracted to dedicated classes:
       - `PaymentStatusTransitionPolicy`,
       - `ShipmentStatusTransitionPolicy`,
       - `OrderStatusTransitionPolicy`,
       with matrix unit guardrails.
     - admin/manual order status update flow now adopts transition policies with transport-safe `422` mapping.
6. Wave 5 in progress (application boundary hardening):
   - completed sub-blocks:
     - checkout payment initiation command handler migrated from ORM model return to typed DTO boundary (`CheckoutPaymentResultDto`);
     - architecture guardrail added for checkout command handlers: no ORM model return types.
     - admin categories application handlers migrated from ORM/paginator return types to typed DTO boundaries:
       - `AdminCategoryResultDto`,
       - `AdminCategoryPaginatedResultDto`,
       with transport response mapped through explicit `data/meta` DTO arrays.
     - architecture guardrail added for admin category command/query handlers: no ORM/paginator return types.
     - admin products application handlers migrated from ORM/paginator return types to typed DTO boundaries:
       - `AdminProductResultDto`,
       - `AdminProductPaginatedResultDto`,
       with explicit category/variant/inventory typed result boundaries.
     - architecture guardrail added for admin product command/query handlers: no ORM/paginator return types.
   - remaining focus: eliminate ORM/paginator return leakage from remaining application handlers via typed result DTO boundaries.

## Confirmed Findings

1. Large services remain overloaded:
   - `CheckoutService`
   - `PaymentWebhookAdapter`
   - `ShippingWebhookAdapter`
2. Application handlers still expose ORM/paginator return types in many paths.

## Locked Constraints

1. Keep `/api/v1/*` envelope backward-compatible (`data/meta/error`).
2. Internal contracts may evolve to typed DTO/value objects.
3. Controller layer remains transport-only.
4. One logical block = one coherent commit-sized change.
5. No silent architecture tradeoffs; all exceptions must be explicit and reversible.
6. For Wave 4+ blocks, depth is mandatory:
   - extract logic into explicit boundary class/service/policy,
   - add deterministic tests for transition/rule matrix,
   - update execution log with checks run.

## Interface/Contract Changes

1. Gateway webhook methods migrate from `array` payloads to typed payload boundaries (`JsonPayload` / typed DTO).
2. Filter contracts migrate to `*FilterDto` inside `app/Application/<Domain>/Dto/*`.
3. `UpdateAdminOrderStatusInputDto` migrates from raw strings to enum-based fields.
4. Application handlers gradually migrate from ORM returns to typed result DTO.
5. Shipping webhook gets async ingestion parity (`ProcessShippingWebhookJob`).

## Implementation Waves

### Wave 0 (1 day) - Governance Reset

1. Set this file as active architecture execution source.
2. Keep historical plans as history, not active source-of-truth.
3. Keep `docs/REFACTORING_EXECUTION_PLAN.md` as operational log only.

DoD:

- no contradictory active plans;
- one current architecture roadmap in execution.

### Wave 1 (2-3 days) - Transport Purity Completion

1. Add application handlers for webhook ingress:
   - `EnqueuePaymentWebhookHandler`
   - `EnqueueShippingWebhookHandler`
2. Keep webhook controllers transport-only:
   - header validation,
   - command dispatch,
   - standardized response.
3. Add async shipping ingestion job (`ProcessShippingWebhookJob`) for parity.
4. Move Password/Verification orchestration to application handlers.
5. Extend public controller architecture test coverage to all API V1 controllers.

DoD:

- API V1 controllers have application-handler dependencies only (except documented reversible exceptions).

### Wave 2 (3-4 days) - Webhook Contract Hardening

1. Replace gateway webhook `array` params with typed payload boundaries.
2. Remove duplicated parse/verify logic across controller/adapter/job.
3. Centralize webhook outcome taxonomy and error mapping.
4. Add shipping webhook API contract scenario to `app:api-contract-smoke`.
5. Add parity feature tests for payment/shipping: signature, duplicate, payload hash mismatch, retry safety.

DoD:

- payment and shipping webhook ingestion share the same architectural pattern.

### Wave 3 (3-5 days) - DTO Discipline Reconciliation

1. Migrate `app/Filters/*` to application-layer `*FilterDto`.
2. Migrate `UpdateAdminOrderStatusInputDto` to enum fields.
3. Ensure `toArray()` boundaries produce scalar-safe transport payloads.
4. Remove dead code and add guardrail against unused legacy payload builders.

DoD:

- DTO naming/placement matches ADR across backend/frontend.

### Wave 4 (1-2 weeks) - Service Decomposition

1. Split `CheckoutService` into focused components:
   - idempotency,
   - inventory reservation,
   - discount resolution,
   - order write orchestration.
2. Split `CartService` into resolver/mutation/result-mapper services.
3. Extract status transition policies for order/payment/shipment.
4. Cover transition matrices and concurrency paths with unit tests.

DoD:

- critical services reduced to orchestration-level complexity;
- status transitions defined in dedicated policy classes.

### Wave 5 (1 week) - Application Boundary Hardening

1. Replace ORM/paginator returns in handlers with typed result DTOs (incremental by domain).
2. Add architecture test: forbid ORM return types in `app/Application/*Handler`.
3. Move auth persistence/query responsibilities behind repository contracts.

DoD:

- application layer no longer leaks ORM types to transport layer.

### Wave 6 (4-5 days) - Frontend Structural Consolidation

1. Extract duplicated route-query logic for admin lists into shared schema-driven helpers.
2. Decompose large admin composables into state/query/mutation/view-model slices.
3. Add deterministic tests for route sync, cancellation, and out-of-order responses.

DoD:

- repeated logic (`>=2` usage) extracted to shared modules;
- admin composables have clear responsibilities.

### Wave 7 (3-4 days) - Observability Modularization

1. Split `ObservabilityService` into ingestion/store/snapshot modules.
2. Split `ObservabilityAlertRouter` into channel senders behind a shared interface.
3. Add unit/feature tests for cooldown, fallback routing, and snapshot correctness.

DoD:

- observability module has explicit internal boundaries and lower coupling.

## Mandatory Test Matrix

1. Architecture tests:
   - full API V1 controller boundary coverage,
   - no regression to array-based contracts,
   - no ORM return leakage in handlers (after Wave 5).
2. Feature tests:
   - webhook parity and idempotency,
   - admin status transition validation,
   - payload hash mismatch and signature failures.
3. Unit tests:
   - transition policies,
   - decomposed checkout/cart components,
   - observability modules.
4. Frontend tests:
   - route-query schema helpers,
   - composable race/cancellation guarantees,
   - API contract assertions.
5. Smoke tests:
   - `app:api-contract-smoke` includes shipping webhook contract,
   - `app:webhook-flow-smoke` remains green with idempotent replay.

## Quality Gate (Strict Sequence)

1. `composer run lint`
2. `composer run analyse`
3. `php artisan test`
4. `npm run lint`
5. `npm run lint:ox`
6. `npm run format:ox:check`
7. `npm run type-check`
8. `npm run test`
9. `npm run build`
10. if routes/controllers changed:
   - `php artisan optimize:clear`
   - `php artisan route:list --path=api/v1/admin/promotions`

## Assumptions and Defaults

1. Priority mode is `Architecture-first`.
2. Public API envelope must remain stable.
3. Any webhook status-code normalization is an explicit later migration.
4. Each completed logical block updates `docs/REFACTORING_EXECUTION_PLAN.md` with executed checks.
5. No block is considered complete until full quality gate is green.
