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
- `npm run type-check`
- `npm run test`
- `npm run build`

## Mandatory post-change production readiness

After any code change, task is considered complete only when production-readiness checks are done.

- Always run the full local quality gate:
- `composer run lint`
- `composer run analyse`
- `php artisan test`
- `npm run lint`
- `npm run type-check`
- `npm run test`
- `npm run build`
- If any command fails, fix the issue and rerun until green.
- Report executed checks and their result in the final update.
- Do not mark work as done after code edits only.
- If routes/controllers were changed, clear optimized caches and run a route smoke-check:
- `php artisan optimize:clear`
- `php artisan route:list --path=api/v1/admin/promotions`

## Change discipline

- Do not perform unrelated refactors in feature fixes.
- Keep commits focused and reversible.
- Document non-obvious decisions in code comments or PR notes.
- If requirements conflict with these rules, clarify first and then implement.
