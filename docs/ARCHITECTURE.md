# Architecture

## Authority

- Active architecture roadmap: `docs/ARCHITECTURE_REFACTOR_NEXT.md`
- Operational execution log: `docs/REFACTORING_EXECUTION_PLAN.md`
- Project policy: `AGENTS.md`
- Repository navigation map: `docs/REPO_MAP.md`
- Domain dependency map: `docs/DOMAIN_MAP.md`

This document defines stable architecture contracts for implementation and refactoring.

## System shape

- Backend: Laravel 12, PHP 8.4
- Frontend: Vue 3 + TypeScript + Vite
- Data: MySQL 8.4, Redis 7
- Style: modularized Laravel monolith with explicit bounded contexts in `Application/*`

Current bounded contexts:

- `Application/Admin/*`
- `Application/Account/*`
- `Application/Auth/*`
- `Application/Catalog/*`
- `Application/Cart/*`
- `Application/Checkout/*`
- `Application/Webhook/*` (via service boundaries and processing pipeline)

## Current implementation snapshot (`2026-03-05`)

- Runtime backend slices are currently implemented through:
  - `app/Http/*` (transport),
  - `app/Application/*` (use-case orchestration + DTO boundaries),
  - `app/Services/*` and `app/Domain/*` (business rules/policies/value objects),
  - `app/Repositories/*` (read/write persistence boundaries).
- `app/Domains/*` exists as modular-monolith convergence skeleton (module READMEs) and is the target location for future incremental slice migration.
- API V1 transport is active for: `Auth`, `Catalog`, `Cart`, `Checkout`, `Account/Orders`, `Admin`, and `Webhook` flows.
- Operational architecture slices are implemented in `app/Support/Observability/*`, `app/Support/Smoke/*`, and `app/Support/Maintenance/*`, with console entrypoints in `app/Console/Commands/*`.
- Architecture guardrails are actively enforced in `tests/Unit/Architecture/*` (layer direction, handler DTO boundaries, controller validation boundaries, documentation governance).

## Modular Monolith Target Layout

Target physical structure (incremental migration, no big-bang rewrite):

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

Each domain module converges toward internal subfolders:

- `Controllers`
- `Services`
- `Repositories`
- `Models`

Migration policy:

1. Keep current `Application/Services/Repositories` boundaries stable while moving slices incrementally.
2. Move one coherent flow per block with tests and contract compatibility.
3. Do not break `/api/v1/*` envelopes or persistence schema contracts during relocation.

## Layer model

### Controller layer (`app/Http/Controllers/*`)

- Transport only: authorize, validate, delegate to Application handlers.
- No business rules, no persistence orchestration.
- API envelope stays backward-compatible for `/api/v1/*`.

### API error contract (`/api/v1/*`)

- All API exceptions render through a single boundary class, `App\Support\Api\ApiExceptionRenderer`, registered in `bootstrap/app.php` via `$exceptions->render(...)`. The renderer maps exception categories to HTTP status, masks 5xx messages to `Internal server error.`, and emits the shared error envelope through `ApiResponse::error()`.
- Error envelope shape: `{ error: { message, request_id?, type, code, validation? } }`.
  - `message`: human-readable text; masked for 5xx.
  - `request_id`: mirrors the `X-Correlation-Id` request header when present.
  - `type`: PHP class basename of the thrown exception (`AuthenticationException`, `ValidationException`, `OrderStaleAggregateException`, etc.). Preserved byte-identical for backward compatibility, but treated as **deprecated-but-stable**: clients should pin on `code` for machine handling. Removing `type` is a separate future breaking change with a migration plan.
  - `code`: stable, additive machine-readable literal from the closed `App\Support\Api\ApiErrorCode` enum. Closed set: `validation_failed`, `unauthenticated`, `forbidden`, `not_found`, `state_transition_not_allowed`, `stale_aggregate`, `webhook_ingress_rejected`, `domain_failure`, `internal_error`. The set can only grow in additive fashion; existing literals never change spelling.
  - `validation`: present only for `ValidationException`, carries Laravel's validator errors map.
- Controllers must throw Symfony `HttpException` subclasses (or domain exceptions) so the renderer boundary stays the sole emit path. Inline `ApiResponse::error()` calls in controllers are forbidden by `ApiControllerDomainExceptionBoundaryTest`, with a documented allowlist for auth and webhook controllers that must catch their application-specific exception to surface a declared status code.
- `error.code` literals may only originate from the `ApiErrorCode` enum; inline string literals in `app/` are forbidden by `ApiErrorCodeStabilityGuardrailTest`.

### Stale-aggregate reliability contract

- `App\Domain\Exceptions\OrderStaleAggregateException` is thrown when an Orders aggregate (typically `Order`) could not be acquired under a row lock because it became stale (concurrently deleted) between the request boundary and the lock acquisition inside the service transaction. This is a concurrency/conflict signal, not a validation failure.
- HTTP rendering: HTTP **409 Conflict** with `error.code = stale_aggregate` and `error.type = OrderStaleAggregateException`.
- Queue rendering: when the same exception propagates inside a queued job (e.g. webhook processing), the job **fails** — no HTTP envelope is produced. Retry semantics follow the standard queue worker contract.
- This contract replaces the previous generic `DomainException` 422 path in `PaymentService::initiate` and `ShippingService::createShipment`. The 422 → 409 promotion is intentional: 409 Conflict is the semantically correct status for stale state under concurrent update.

### Application layer (`app/Application/*`)

- Command/query handlers orchestrate use-cases.
- Handlers return typed DTO boundaries, not ORM models.
- Depends on contracts, policies, DTO/value boundaries.

### Domain/Service layer (`app/Services/*`, `app/Domain/*`)

- Business rules, transition policies, idempotency, deterministic orchestration.
- Side effects that require committed state must be after-commit safe.
- Domain errors use typed exceptions where available.

### Repository layer (`app/Repositories/*`)

- Persistence/query composition only.
- No authorization decisions, no transition decisions, no business outcomes.
- Cross-context read leakage is forbidden.

## Dependency rules

Allowed dependency direction (high to low):

1. `Controller -> Application`
2. `Application -> Contracts/DTO/Domain/Services/Repositories-contracts`
3. `Services -> Domain + Repositories + Infrastructure contracts`
4. `Repositories -> Eloquent/DB query primitives`

Forbidden:

- `Controller -> Services` or `Controller -> Repositories`
- `Application -> Http transport objects`
- `Repository -> Controller/Request/Policy/Transition outcome logic`
- Frontend page components with embedded API transport logic or business rules

## Reliability contracts

- Input validation via Form Requests or explicit typed DTO mapping.
- Authorization via policies/middleware.
- Sanctum bearer tokens have a finite configured lifetime and persist explicit expiration timestamps.
- Authenticated API routes, including optional-auth cart/checkout entrypoints, revalidate `User.is_active`; an inactive-token attempt revokes all tokens before returning `401`.
- Logout revokes only the current bearer token, while password reset revokes every token for the user.
- Registration and password reset share one password policy: at least 12 characters with letters and numbers.
- Login throttling is config-driven and scoped by normalized email hash plus client IP.
- Every login attempt performs one password-hash verification; unknown, invalid, and inactive credentials keep the same generic `422` error envelope.
- Auth credential-sensitive flows emit a structured security audit trail (`auth.login.succeeded`, `auth.login.failed`, `auth.logout`, `auth.token.issued`, `auth.token.revoked` with `scope` and `reason`, `auth.password.reset.requested`, `auth.password.reset.completed`, `auth.email.verified`) into the observability channel through the `AuthAuditLogger` contract; the context is an explicit whitelist (correlation id, user id or `sha256` email hash on failure paths, client IP, user-agent) and repositories stay persistence-only.
- Eloquent strict runtime mode (`shouldBeStrict`) is wired in `AppServiceProvider::boot()` and active in every non-production environment, surfacing lazy loads, silently discarded attributes, and missing-attribute access in dev/test while production behavior stays unchanged; the allowlist can only shrink.
- All Eloquent datetime attributes use `immutable_datetime` casts and the global date resolver is bound to `CarbonImmutable`, so model timestamp attributes and `now()` share the immutable contract end to end.
- One correlation id joins HTTP ingress, queued processing, and side-effect logs across payment, shipping, and notification flows: the `X-Correlation-Id` request header set by `CorrelationIdMiddleware` is resolved through the `CorrelationContext` accessor, captured into every queued job payload (`correlationId` scalar, preserving scalar-only payload discipline), restored into structured log context at the start of `handle()`, and forwarded by webhook enqueue handlers so `webhook.processing_failed` carries the true ingress correlation instead of the event-id fallback; no new PII is introduced.
- Promotion discount calculation crosses the domain boundary as an exact decimal string, never as float: `Money::percentage()` accepts a decimal-string rate (up to four decimal places) and computes the discount through integer per-million arithmetic with `PHP_ROUND_HALF_UP`, so fractional percentages and cent-edge rounding stay deterministic; `CheckoutDiscountResolver::calculateDiscountTotal()` is statically typed (`PromotionType`, `string`, `Money`) and consumes the Eloquent `decimal:2` cast of `promotions.value` directly, defending the PERCENT branch against rates outside `[0, 100]` even when the call does not pass through the HTTP validator.
- Checkout idempotency retention is split into two independently configurable windows resolved through bounded positive-integer validation in `config/checkout.php`: `checkout.idempotency.pending_minutes` (default `30`, max `10080`) governs unresolved idempotency records before they can be replaced, and `checkout.idempotency.completed_hours` (default `24`, max `720`) governs how long a finalized record keeps serving the original order back to replays; a misconfiguration fails fast at config resolution time.
- Checkout/webhook flows are idempotent and transaction-safe.
- Status transitions are explicit and matrix-guarded.
- Critical behavior is covered by architecture guardrails + feature/unit tests.

## Documentation update policy

When architecture-relevant behavior changes:

1. Update this file if the contract itself changed.
2. Update `docs/ARCHITECTURE_REFACTOR_NEXT.md` progress or waves when execution scope changes.
3. Update `docs/REFACTORING_EXECUTION_PLAN.md` with completed logical block and checks.
