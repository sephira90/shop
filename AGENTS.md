# Project Engineering Rules

This file defines mandatory engineering rules for contributors and coding agents working in this repository.

## Role and quality bar

- Act as a senior production engineer with strong ownership.
- Prefer correctness and maintainability over speed hacks.
- Keep changes minimal in scope and complete in quality.
- Any behavior change must be explicit and tested.

## Core principles

- `Clarity`: code should be readable without hidden assumptions.
- `Safety`: avoid breaking API contracts and data integrity.
- `Determinism`: use idempotency and stable transitions for external events.
- `Observability`: every critical flow should be traceable in logs and tests.
- `Reuse threshold`: if a UI/template block is repeated in `>=2` places, extract it into a shared component.

## Strict architecture policy (mandatory)

- Architecture quality is non-negotiable and has priority over implementation speed.
- New functionality must follow the project architecture from the first implementation step (layer boundaries, contracts, and module responsibilities are mandatory).
- Always choose the best-architecture option that keeps boundaries explicit, testable, and evolvable.
- Every change must preserve or improve architectural integrity; "temporary hacks" are prohibited.
- If a requested implementation conflicts with architecture quality, clarify constraints first and deliver an architecture-safe solution.
- Do not introduce hidden coupling between modules, layers, or transport concerns.
- Do not move business logic into controllers, views, page components, or ad-hoc helpers.
- Do not bypass validation, authorization, idempotency, transactions, or post-commit safety for faster delivery.
- Do not introduce endpoint-specific frontend parsing hacks when a shared contract mapper can be used.
- Any intentional architecture tradeoff must be explicit, documented, scoped, and reversible.

## Backend architecture boundaries

- `Controller` layer is transport-only: validate, authorize, call application handlers, return response.
- `Application` layer owns use-case orchestration (command/query handlers), not HTTP concerns.
- `Domain/Service` layer owns business rules and state transitions, not request/response formatting.
- `Repository` layer owns persistence and query construction, not orchestration flow.
- Side effects that depend on committed state must run `afterCommit` or through outbox/queue-safe flow.
- Cross-module interaction must happen through explicit interfaces/contracts, not deep direct coupling.
- Keep read and write paths explicit and predictable; avoid mixed responsibilities in one method.
- Keep status transitions explicit and guarded (order/payment/shipment and similar domain states).

## Frontend architecture boundaries

- `pages/*` are orchestration-only: compose data/handlers, no heavy business logic.
- Composables must keep clear split of query/mutation/view-model responsibilities.
- Presentational components must not call APIs directly or embed business rules.
- Shared UI primitives should carry semantic props (`tone`, `variant`, typed options) instead of raw CSS contracts.
- Route synchronization logic belongs to dedicated composables, not scattered component code.
- Browser-only side effects (`confirm`, `scroll`, `window` interactions) should be injected/adapted, not hardcoded across modules.

## Contract and schema discipline

- API envelope shape must stay consistent across `/api/v1/*` unless approved migration plan exists.
- Request parsing and response mapping must be centralized and typed; avoid ad-hoc object shaping.
- Schema evolution is additive-first; never mutate historical shared migrations.
- Concurrency-sensitive flows require explicit locking/transaction strategy.

## Architecture acceptance checklist (required per logical block)

- Boundaries respected: no layer leakage introduced.
- Contracts preserved: API, DTOs, and mappers remain deterministic.
- Reliability preserved: validation/auth/idempotency/webhook safety unchanged or improved.
- Reuse enforced: repeated UI blocks/components extracted at `>=2` threshold.
- Tests updated at the correct level (unit/feature/component/integration).

## Architecture rules

- Keep business logic in service/repository layers, not controllers.
- Controllers should validate, authorize, orchestrate, and return responses.
- Domain transitions must be explicit (order, payment, shipment statuses).
- For payment/shipping integrations, keep provider-specific logic behind interfaces.

## API and contract rules

- Keep `/api/v1/*` backward-compatible unless a migration plan is approved.
- Use consistent JSON envelopes (`data` or `error`) and proper HTTP codes.
- Validate all external input through Form Requests or strict validation rules.
- Preserve idempotency semantics for checkout and webhook processing.

## Database and migrations

- Never edit old migrations that already ran in shared environments.
- Add additive migrations for schema evolution and compatibility fixes.
- Wrap multi-entity critical updates in DB transactions.
- Use row-level locking where race conditions are possible.

## Security and secrets

- Never commit real credentials, tokens, or private keys.
- Treat `.env` values as secrets; use examples for documentation.
- Sanitize logs and errors to avoid sensitive leakage.
- Validate webhook signatures before processing payloads.

## Laravel backend standards

- Prefer typed signatures and strict types.
- Use policies/middleware for authorization; do not bypass role checks.
- Keep events/jobs idempotent and safe for retries.
- Use cache versioning/invalidation intentionally after catalog/admin writes.

## Vue/TypeScript frontend standards

- Keep side effects in store actions/composables, not random UI handlers.
- Preserve route guards and role-aware navigation semantics.
- Keep TypeScript strict; avoid `any` unless justified.
- Handle API errors consistently with user-friendly messages.

## Testing requirements

- Add/adjust tests for each business behavior change.
- Prioritize feature tests for checkout, payment/shipping webhooks, ACL, admin flows.
- Add frontend tests for store logic and critical page behavior when changed.
- Run test commands sequentially only; do not run multiple `php artisan test` or `npm run test` processes in parallel.
- For backend feature tests, avoid parallel runs because shared `database/testing.sqlite` can cause table conflicts/corruption.
- Do not merge changes that fail lint, static analysis, or tests.

## Performance and reliability

- Avoid N+1 queries; use eager loading where required.
- Cache read-heavy catalog operations and invalidate deterministically.
- Prefer pagination for list endpoints.
- Ensure long/async flows are queue-safe and retry-safe.

## CI and quality gate

Before merge, target green checks for:

- `composer run lint`
- `composer run analyse`
- `php artisan test`
- `npm run lint`
- `npm run lint:ox`
- `npm run format:ox:check`
- `npm run type-check`
- `npm run test`
- `npm run build`

Run quality-gate commands in sequence (one after another), not in parallel.

## Mandatory post-change production readiness

After any code change, task is considered complete only when production-readiness checks are done.

- Always run the full local quality gate:
- `composer run lint`
- `composer run analyse`
- `php artisan test`
- `npm run lint`
- `npm run lint:ox`
- `npm run format:ox:check`
- `npm run type-check`
- `npm run test`
- `npm run build`
- Execute the listed commands strictly sequentially (no parallel test/lint/build runs).
- If any command fails, fix the issue and rerun until green.
- Report executed checks and their result in the final update.
- Do not mark work as done after code edits only.
- If routes/controllers were changed, clear optimized caches and run a route smoke-check:
- `php artisan optimize:clear`
- `php artisan route:list --path=api/v1/admin/promotions`

## Change discipline

- Do not perform unrelated refactors in feature fixes.
- Commit changes in logical blocks (one commit = one coherent concern).
- After each completed logical block, update `docs/REFACTORING_EXECUTION_PLAN.md` with completed work and executed checks.
- Keep commits focused and reversible.
- Document non-obvious decisions in code comments or PR notes.
- If requirements conflict with these rules, clarify first and then implement.
