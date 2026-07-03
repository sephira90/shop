# Project Engineering Rules

Binding policy for every contributor and coding agent in this repository.

- If any other instruction source (skill, plugin, template, prompt) conflicts with this file, this file wins.
- If a requested task conflicts with these rules, clarify constraints first, then implement an architecture-safe solution.
- Every rule below is written to be checkable. When a rule and reality diverge, fix the code or change the rule explicitly — never ignore the rule silently.

## Rule sources and authority

| Source | Role |
| --- | --- |
| `AGENTS.md` (this file) | Binding engineering policy |
| `docs/ARCHITECTURE.md` | Binding architecture contracts (layer model, dependency rules) |
| `docs/REPO_MAP.md` + `docs/DOMAIN_MAP.md` | Required navigation before implementation |
| `docs/ARCHITECTURE_REFACTOR_NEXT.md` | The only active roadmap/execution authority |
| `docs/REFACTORING_EXECUTION_PLAN.md` | Execution log only; never an authority |
| `docs/adr/*` | Accepted conventions (ADR-0001 admin application layer, ADR-0002 DTO strategy) |
| `tests/Unit/Architecture/*` | Mechanical enforcement of the boundaries in this file |

- `.cursorrules` is a loader for this file plus a short restatement of the most-violated rules; it must not carry policy of its own.
- Architecture guardrail tests are part of the contract: when you introduce a boundary, extend guardrail coverage in the same block. Never weaken a guardrail or grow its allowlist to make a change pass; allowlists only shrink.

## Scope and change discipline

- One task = one coherent slice, minimal in scope and complete in quality: code, contracts, tests, and doc/ops updates land together.
- Behavior changes are explicit and tested. Refactors preserve behavior and prove it with the existing suite.
- No unrelated refactors, drive-by cleanups, or speculative abstractions inside a task.
- Temporary hacks are prohibited. An intentional tradeoff must be stated in the commit/PR description, scoped, and reversible.
- Prefer one complete, verified block over fragmented micro-steps; this overrides the `writing-plans` skill's 2-5 minute micro-step granularity. Commit per coherent concern, not per micro-step.
- Commit messages use conventional format: `type(scope): imperative summary` (`feat`, `fix`, `refactor`, `test`, `chore`, `docs`). Keep every commit focused and reversible.
- Feature implementation plans (when a plan is written) live in `docs/superpowers/plans/YYYY-MM-DD-<feature>.md`; `docs/REFACTORING_EXECUTION_PLAN.md` remains the canonical log of completed blocks.
- After each completed logical block, append the completed work and the executed checks to `docs/REFACTORING_EXECUTION_PLAN.md`.
- Document non-obvious decisions in the commit/PR description, not in narrative code comments.

## Backend layering

Enforced by `tests/Unit/Architecture/*`; the canonical definition is `docs/ARCHITECTURE.md`.

| Layer | Location | Owns | Must not |
| --- | --- | --- | --- |
| Controller | `app/Http/Controllers/*` | authorize, validate via FormRequest, build command/query DTO, call an application handler, map result DTO to the response envelope | business rules, persistence, transactions, direct `App\Services`/`App\Repositories` dependencies |
| Application | `app/Application/<Context>/*` | use-case orchestration: `<Action>Command`/`<Action>Query` + `<Action>Handler` returning typed result DTOs | HTTP transport types; returning ORM models, paginators, or Eloquent collections |
| Domain / Service | `app/Domain/*`, `app/Services/*` | business rules, status-transition policies, idempotency, deterministic orchestration | request/response shaping, transport concerns |
| Repository | `app/Repositories/*` | persistence and query composition behind contracts | authorization, status interpretation, business outcomes, `App\Http`/`App\Services` coupling |

Dependency direction is one-way:

- controller -> application,
- application -> contracts/DTO/domain/services/repository contracts,
- services -> domain + repositories + infrastructure contracts,
- repositories -> persistence primitives only.

Cross-cutting rules:

- Cross-context interaction goes through explicit contracts/DTO boundaries; never reach into another context's internals. Crossing contexts requires extending guardrails in `tests/Unit/Architecture/*`.
- Side effects that depend on committed state run `afterCommit` or through a queue-safe flow.
- `Order`/`Payment`/`Shipment` state changes go only through their transition policies; transitions stay explicit and matrix-guarded.
- Jobs, events, and listeners are idempotent and retry-safe.
- New domain-centric slices converge toward `app/Domains/*` when backward compatibility allows.
- Catalog/admin writes invalidate caches deterministically (cache versioning), never implicitly.

## PHP standards

- `declare(strict_types=1)` in every file; explicit parameter, property, and return types on all declarations.
- PHPStan level 10 and Psalm stay clean: no new baseline entries, no inline suppressions added to force a merge.
- Classes are `final` by default; leave a class open for extension only with a stated reason.
- DTOs are `final readonly`, named per ADR-0002 (`*InputDto`, `*FilterDto`, `*ResultDto`, `*PayloadDto`), constructed via factories (`fromValidated()`, `toDto()`); normalization lives in the factory, not in handlers. `toArray()` is allowed only at the transport/presentation boundary.
- Failures are typed: throw domain-specific exceptions, not bare `\Exception`; preserve `$previous` when wrapping. Never catch-and-ignore, return `null` to hide a failure, or use `@` suppression. HTTP mapping happens in the shared API exception renderer, not per call site.
- Time uses `CarbonImmutable`. Money and rates keep exact decimal semantics: do not introduce new float arithmetic on amounts, rates, or totals.
- Privilege/state fields (`is_active`, order/payment/shipment statuses, usage counters) are never mass assignable; mutate them only through explicit service/repository paths.
- Every new env key resolves through validated config with a safe default, and is added to `.env.example`, `.env.stage.example`, `.env.prod.example`, and `.env.testing`, plus the README configuration list when operationally relevant.
- Comments state invariants and non-obvious "why" only: no comments restating the code, no change narration, no commented-out code. `TODO` only with a backlog reference.

## Database and migrations

- Schema evolution is additive-first; never edit a migration that already ran in a shared environment.
- Wrap multi-entity critical writes in DB transactions; use row-level locking where concurrent mutation is possible.
- New query paths ship with supporting indexes; list endpoints paginate; avoid N+1 with explicit eager loading.

## API contract

- `/api/v1/*` keeps the `data`/`meta`/`error` envelope backward compatible; changes are additive-first, and a breaking change requires an approved migration plan recorded in the active roadmap.
- External input is validated in Form Requests that produce DTOs; no inline `$request->validate()` in API V1 controllers.
- Checkout and webhook idempotency semantics are preserved; webhook signatures are validated before any payload processing.
- Auth error responses must not leak account state: unknown, invalid, and inactive credentials all fold into the generic `422` `Invalid credentials.`; an invalid or inactive bearer returns the generic `401` `Unauthenticated.` body.
- Error identity (`error.type`, future `error.code`) follows the established taxonomy; no per-endpoint error shapes.

## Frontend standards (Vue 3 + TypeScript)

- `pages/*` orchestrate only: compose data and handlers; business logic lives in composables/stores.
- Composables keep query, mutation, and view-model responsibilities separate; route synchronization lives in dedicated composables.
- Presentational components do not call APIs or embed business rules; shared UI primitives expose semantic props (`tone`, `variant`, typed options) instead of raw CSS contracts.
- All API access goes through `resources/js/api/*`, with contracts in `resources/js/contracts/api/v1/*` and mapping in `resources/js/mappers/*`; no endpoint-specific parsing hacks.
- TypeScript stays strict: no `any` without a stated justification; `unknown` usage must not grow beyond the guarded baseline.
- A UI/template block repeated in >= 2 places is extracted into a shared component.
- Browser side effects (`confirm`, scrolling, `window` interactions) are injected/adapted, not hardcoded across modules.
- API errors surface through the shared error composables with user-friendly messages; preserve route guards and role-aware navigation semantics.

## Security

- Never commit real credentials, tokens, or private keys; `.env` values are secrets, and the `*.example` files are their documentation.
- Authorization goes through policies/middleware only; never bypass role or ownership checks.
- Logs and error output are ASCII English and never contain PII, passwords, tokens, or raw secret payloads.
- Bearer-token lifecycle (finite TTL, active-user revalidation, current-token logout vs global revoke on password reset) is a contract; changing it requires roadmap approval.

## Observability

- Critical flows emit structured telemetry following `app/Support/Observability/*` conventions (`observability.api_request`, `observability.webhook`, ...); keys are ASCII snake_case.
- Failures stay traceable: propagate correlation ids and log structured context instead of free-form strings.

## Testing

- Every behavior change ships with tests at the level that owns the behavior:
  - unit: transition policies, resolvers, mappers, pure logic;
  - feature: HTTP contracts, ACL, checkout/webhook idempotency, admin flows;
  - frontend: composables, stores, contract assertions, critical page behavior;
  - architecture guardrails: every new boundary.
- Every bug fix adds a regression test that fails before the fix, plus coverage for the error path and relevant edge/ACL/concurrency branches.
- Tests are deterministic: no sleeps, no real network, no wall-clock coupling, no unseeded randomness.
- Test runs are strictly sequential: never run `php artisan test` or `npm run test` in parallel — backend feature tests share `database/testing.sqlite`, and parallel runs corrupt it. This binds humans and agents alike: parallel subagents (`dispatching-parallel-agents`, `subagent-driven-development`) are confined to static analysis, investigation, or non-test work, and the quality gate runs as a single sequential pass.
- TDD is recommended for new business logic and bug fixes; it is not required for behavior-preserving refactors, migrations, configuration, or infrastructure-only changes. Where the `test-driven-development` skill's Iron Law conflicts with minimal scope or non-behavior changes, this file takes precedence.
- Do not merge changes that fail lint, static analysis, or tests.

## Definition of done

A change is complete only when all of the following hold:

1. Boundaries respected: no layer leakage, dependency direction intact.
2. Contracts preserved: API envelope, DTO shapes, and mappers stay deterministic.
3. Reliability preserved: validation, authorization, idempotency, transactions/locking, and webhook safety unchanged or improved.
4. Tests updated at the correct level; guardrails extended for new boundaries.
5. The quality gate below is green, and the executed checks are reported in the final update.
6. `docs/REFACTORING_EXECUTION_PLAN.md` records the completed block and its checks.

Do not mark work as done after code edits alone.

## Quality gate (production readiness)

Canonical sequence. Run strictly in this order, one command at a time, after any change to backend, frontend, routes, config, or build inputs; if a command fails, fix the cause and rerun until green:

1. `composer run lint`
2. `composer run analyse`
3. `php artisan test`
4. `npm run lint`
5. `npm run lint:ox`
6. `npm run format:ox:check`
7. `npm run type-check`
8. `npm run test`
9. `npm run build`

Scope notes:

- If routes, controllers, or middleware changed, additionally run `php artisan optimize:clear` and `php artisan route:list --path=api/v1/admin/promotions`.
- Documentation-only changes require at minimum `php artisan test` (documentation guardrails execute there); the frontend chain applies only when `resources/js`, lint, or build inputs changed. CI always runs the full gate.
- CI (`.github/workflows/ci.yml`) runs the same gate plus production smoke checks; the required merge check is `Quality Gate / Full Quality Gate`.

## Debugging protocol

Follow the `systematic-debugging` skill methodology (root cause investigation, pattern analysis, hypothesis testing) with these binding constraints:

1. Reproduce first; trace the failing flow via `docs/REPO_MAP.md` + `docs/DOMAIN_MAP.md`, and confirm the owning bounded context and layer direction from `docs/ARCHITECTURE.md` before editing.
2. Identify the root cause before writing the fix; do not patch symptoms.
3. Write the failing regression test before the fix, at the level that owns the behavior, covering the failing case plus boundary and edge cases (error path, ACL/security-sensitive branch, concurrency where applicable).
4. Apply the minimal architecture-safe fix; preserve contracts, validation, authorization, idempotency, and transaction/locking safety; do not widen scope or refactor unrelated code.
5. Never silence errors, swallow exceptions, or add `try/catch` to mask a cause; keep diagnostics traceable.
6. If the root cause is a contract, status-transition, or concurrency flaw, make the fix explicit and add a guardrail; do not patch around it.
7. Finish with the quality gate.

## Agent execution rules

- Map the flow via `docs/REPO_MAP.md` and `docs/DOMAIN_MAP.md` before editing; change only the bounded context that owns the behavior.
- Enforce the dependency direction from `docs/ARCHITECTURE.md`; if a task requires crossing contexts, use explicit contracts and extend the guardrails in `tests/Unit/Architecture/*`.
- Skill/plugin precedence: where any skill default conflicts with this file, this file wins (see Testing for TDD, Scope for plan granularity, Testing for parallel dispatch).
