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
- Checkout/webhook flows are idempotent and transaction-safe.
- Status transitions are explicit and matrix-guarded.
- Critical behavior is covered by architecture guardrails + feature/unit tests.

## Documentation update policy

When architecture-relevant behavior changes:

1. Update this file if the contract itself changed.
2. Update `docs/ARCHITECTURE_REFACTOR_NEXT.md` progress or waves when execution scope changes.
3. Update `docs/REFACTORING_EXECUTION_PLAN.md` with completed logical block and checks.
