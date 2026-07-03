# Architecture Refactor Next (Architecture-First)

Program start: `2026-03-05`
Last revised: `2026-07-03` (Q1 closure)
Last review intake: `2026-06-27`
Status: `Active`
Priority mode: `Architecture-first`

## Execution Authority

1. This file is the active architecture execution source-of-truth: direction, pending work, sequencing, and acceptance criteria.
2. Historical plans in `docs/*PLAN*.md` are archival references only and must not be used as active execution authority.
3. `docs/REFACTORING_EXECUTION_PLAN.md` is the operational execution log: the complete dated record of every completed block and its executed checks lives there, not here.
4. Binding policy and the canonical quality gate live in `AGENTS.md`; architecture contracts live in `docs/ARCHITECTURE.md`.

## Summary

The program strengthens an already-layered Laravel + Vue ecommerce monolith into a modular monolith under `app/Domains/*`, without breaking the `/api/v1/*` envelope (`data`/`meta`/`error`) or persistence contracts, with a strict quality gate after each logical block.

Current position:

- Waves `0-24` are complete (transport purity, webhook hardening, DTO discipline, service decomposition, application/frontend boundary hardening, observability/smoke/operations modularization, governance and release guardrails, PHPStan level 10 with no baseline).
- All promoted audit blocks through safety-concurrency block `55` are closed; `Backlog F3` items `77` (token lifecycle), `78` (credential hardening), and `79` (auth security audit trail), plus breaking-change `A1` (auth anti-enumeration contract), are closed.
- Verified-intake block `Q1` (strict Eloquent runtime guardrails + immutable dates) is closed.
- The active block is `Q2` (supply-chain audit gate); the full order is fixed in the Execution Queue below.
- A verified code-review intake (`2026-07-03`) promoted seven quality/reliability blocks: strict runtime guardrails (`Q1`), supply-chain audit gate (`Q2`), queue correlation propagation (`A2`), order-lifecycle reconciliation (`A1`), Psalm ladder (`Q3`), frontend hardening (`Q4`), and an OpenAPI contract source (`S1`).
- The end-state direction is physical convergence to `app/Domains/*`, defined by Convergence Waves `C0-C7` (pending promotion, after `S1`).

## Direction Policy

1. Program direction is singular: move strictly through the Execution Queue in architecture-first order.
2. End-state target is incremental convergence to a modular monolith under `app/Domains/*`.
3. Each newly promoted block must state how it advances modular-monolith convergence while preserving API/DB backward compatibility.

## Architectural Strengths To Preserve

Verified by audit (`2026-03-01`) and revalidated by review intake (`2026-06-27`):

1. The application layer follows CQRS with typed DTO return boundaries; handlers must not regress to ORM, paginator, or resource return types.
2. API V1 controllers are transport-only shells; business logic must not move back into controllers.
3. Checkout, cart, and webhook orchestration is decomposed into focused collaborators; evolve by composition, not by re-collapsing into larger services.
4. Admin frontend flows use decomposed composables and shared mutation pipelines; extend those primitives instead of reintroducing page-local logic.
5. Payment and shipping webhooks share a unified ingress -> transition -> orchestration pattern; changes must preserve parity.
6. Architecture guardrails (`tests/Unit/Architecture/*`) are a first-class mechanism: every new boundary extends guardrail coverage; allowlists only shrink.

## Program Status Registry

Detailed completion narrative, per-block file lists, and executed checks: `docs/REFACTORING_EXECUTION_PLAN.md`. This registry is the status index only.

### Waves (all complete)

| Wave | Scope | Outcome |
| --- | --- | --- |
| 0 | Governance reset | This file became the single active roadmap; historical plans archived |
| 1 | Transport purity | All API V1 controllers depend on application handlers only; async shipping ingress parity |
| 2 | Webhook contract hardening | Typed payload boundaries, shared taxonomy, payment/shipping parity tests |
| 3 | DTO discipline | `*FilterDto` migration, enum status inputs, scalar-safe `toArray()`, legacy artifact guardrails |
| 4 | Service decomposition | Checkout/cart/webhook collaborators; Order/Payment/Shipment transition policies with matrix tests |
| 5 | Application boundary hardening | Typed result DTOs across all handlers; auth behind repository contracts; ORM-return guardrails |
| 6 | Frontend structural consolidation | Admin query/mutation/view-model decomposition; shared route-sync, pipelines, race guards |
| 7 | Observability modularization | Metric store, snapshot builder, alert router/channels split behind contracts |
| 8 | Operations hardening | Cleanup retention, on-call drill plan/escalation extracted; ops docs normalized |
| 9 | Governance/operational guardrails | Orchestration-only command shells; docs/config drift guardrails |
| 10 | Observability report modularization | Options/threshold/output boundaries; command orchestration-only |
| 11 | Smoke scenario modularization | Scenario registries/runners/output builders for all three smoke commands |
| 12 | Shared smoke infrastructure | Shared options/rollback/selection/output contracts; per-command duplication removed |
| 13 | Command contract and scheduler guardrails | Signature/scheduler wiring guardrails; nested invocation contract |
| 14 | Release and CI guardrails | Canonical composer aliases; CI/deploy/docs parity guardrails |
| 15 | Account orders extraction | Canonical `/account/orders*` read model + legacy aliases; summary/detail split |
| 16 | Category selector decoupling | `/admin/categories/options` endpoint; shared frontend options state |
| 17 | Maintenance cleanup strategy | Resource-based plan, batched deletion, cleanup indexes |
| 18 | Read repository split | Bounded admin/catalog/account read repos; summary projector; product write collaborators |
| 19 | Provider hygiene | Concern-specific providers; bootstrap-only `AppServiceProvider`; container guardrails |
| 20 | Archive/documentation hygiene | Archival banners + documentation authority guardrails |
| 21 | Transport validation consistency | `CatalogIndexRequest`; idempotency-key middleware; inline-validate guardrail |
| 22 | Frontend price formatting | Canonical `formatPrice`; catalog/checkout/auth composable coverage |
| 23 | Static analysis hardening | Baseline removed; levels 7 -> 10 closed with `0` errors (with post-wave slices) |
| 24 | Guardrail expansion | Repository business-decision, queued-job safety, policy completeness matrix guardrails |

### Promoted blocks (complete)

| Block | Source | Outcome |
| --- | --- | --- |
| Backlog A | Audit v1 P0 | Cart mutation locking, webhook receipt dedupe, pay transport validation, SPA `401/403` handling |
| Backlog B | Audit v1 | Centralized `DomainException` rendering, coupon policy matrix, cache-refresh authorization |
| Backlog C | Audit v1 | Shared assertion primitives, checkout result state, storage adapter boundary |
| Backlog D (slices) | Audit v1 | `CheckoutPlaceOrderOrchestrator`, shipping-cost resolver contract, `OrderPaymentStatusResolver`, `Money` foundation |
| Backlog E (slices) | Audit v1 | Factories replace seeder coupling; status-transition domain events + metrics/notifications; typed `StatusTransitionSource`; docker-compose stack |
| Backlog F (items 1-3) | Audit v2 P0 | Order direct-transition matrix guard, cart remove validation, `CartPolicy` |
| Backlog G (items 4-8) | Audit v2 P1 | Cart/order `Money` write+read paths, service/repository contracts + container binding tests, atomic metric counters |
| Backlog H (items 9-11) | Audit v2 P1 | Canonical `canTransition` matrix, webhook failure logging, `CartException`/`CheckoutException`/`OrderTransitionException` taxonomy |
| Block 55 | Audit v2 addendum | Items `39/41/43/44/45`: cart ownership guard, counter fillable denylist, inventory release on first cancellation, cart/admin-order transactions with row locks (`40` false-positive, `42` regression watch, `47` business decision item) |
| Governance slices | — | `docs/ARCHITECTURE.md`, `REPO_MAP`/`DOMAIN_MAP` + alias, `app/Domains/*` skeleton, layer-direction and map governance guardrails |
| F3 item 77 + A1 | Audit v2 P0 / approved BC | Finite Sanctum TTL with persisted `expires_at`, `active.api.user` revalidation with global revoke, current-vs-global revoke split; de-enumerated inactive-login contract (`422 Invalid credentials.` / generic `401`) |
| F3 item 78 | Audit v2 P0/P1 | Shared 12-character letters-and-numbers password policy; email+IP login limiter; one bcrypt verification for every login attempt |
| F3 item 79 | Audit v2 P1 | `AuthAuditLogger` contract writing structured `auth.audit.*` events (login s/f, logout, token issued/revoked with scope+reason, password reset request/completed, email verified) into the observability channel; explicit context whitelist with `sha256` email hash on failure paths; repositories stay persistence-only |
| Q1 | Verified intake (`2026-07-03`) | `Model::shouldBeStrict()` wired in `AppServiceProvider::boot()` gated on non-production; strict-mode violations fixed at source (no allowlist); `Date::use(CarbonImmutable::class)` and all 16 model date casts migrated to `immutable_datetime`; guardrail enforces wiring and forbids mutable casts |

## Execution Queue

Locked order. A block starts only when the previous one is closed in the execution log (exception: `R3` may run any time after `R1`).

| # | Block | Priority | State |
| --- | --- | --- | --- |
| 1 | `F3-79` Auth security audit trail | P1 | **Closed** |
| 2 | `Q1` Strict Eloquent runtime guardrails and immutable dates | P1 | **Closed** |
| 3 | `Q2` Supply-chain audit gate (CI + dependabot) | P1 | **Active — next up** |
| 4 | `A2` Correlation propagation across the queue boundary | P1 | Defined, waiting |
| 5 | `R1` API error contract and stale-aggregate taxonomy | P1 | Defined, waiting |
| 6 | `R2` Exact promotion arithmetic and idempotency retention (with Backlog G items `4/5`, I2 item `59`) | P1/P2 | Defined, waiting |
| 7 | `R3` Alert delivery outcome observability | P2 | Defined, waiting (eligible any time after R1) |
| 8 | `A1` Order lifecycle reconciliation and stuck-state detection | P1 | Defined, waiting |
| 9 | Security intake items `80/81` (mass-assignment surface, transport security baseline) | P1 | Candidate — requires promotion |
| 10 | Security intake items `82/83` (data-at-rest minimization, security guardrails) | P2 | Candidate — requires promotion |
| 11 | `Q3` Psalm ladder and scope parity | P2 | Defined, waiting |
| 12 | `Q4` Frontend type/test hardening | P2 | Defined, waiting |
| 13 | `S1` OpenAPI contract source of truth | P1 | Defined, waiting (requires `R1` closed) |
| 14 | Convergence waves `C0-C7` (modular monolith migration) | P1 | Defined below — each wave requires promotion |

Sequencing rationale: `Q1`/`Q2`/`A2` are small guardrail-first blocks placed after the locked `F3` sequence and before `R1` — they tighten regression detection for every later block and do not touch the fixed security-intake order. `A1` precedes the `80/81` promotion decision because it closes a verified customer-impacting silent-loss window. `S1` lands after `R1` (the `error.code` taxonomy belongs in the spec) and before `C0` (module API freeze references the spec).

Remaining audit v1/v2 backlog not listed here stays candidate-only per the Backlog Intake Rule.

## Active Block Definitions

### F3-79 (2-3 days) - Auth Security Audit Trail

Problem: login success/failure, token issuance/revocation, logout, and password-reset completion emit no structured security events; incident analysis of brute force or stolen-token reuse relies on indirect rate-limit symptoms.

Steps:

1. Introduce an `AuthAuditLogger` contract in `app/Application/Auth/Contracts` with an infrastructure implementation writing structured records to the observability log channel. Repositories stay persistence-only: emission happens in application handlers and auth support services, never in `app/Repositories/*`.
2. Emit a fixed event taxonomy: `auth.login.succeeded`, `auth.login.failed`, `auth.logout`, `auth.token.issued`, `auth.token.revoked` (with `scope: current|all` and `reason: logout|password_reset|inactive_user`), `auth.password.reset.requested`, `auth.password.reset.completed`, `auth.email.verified`.
3. Context contract: correlation id, user id or `sha256` email hash (never raw email on failure paths), client IP, user-agent. Passwords, tokens, and token ids in plaintext are prohibited; context keys are an explicit whitelist.
4. Wire the events into the existing observability pipeline so `app:observability-report` windows can expose auth-failure rates; alerting thresholds stay out of scope (`R3` owns delivery semantics).

Tests:

- unit: logger context shaping, key whitelist (leak test asserts no non-whitelisted keys), event-name taxonomy stability;
- feature: each critical flow (login success/failure, logout, reset request/completion, inactive-bearer revocation, verification) writes exactly its expected audit record;
- guardrail: auth handlers emit audit calls on credential-sensitive paths; no `Log::` usage added inside `app/Repositories/*`.

DoD: every listed event is observable with correlation id and safe identity context; no secret/PII leakage; README observability section lists the auth audit events; quality gate green.

Convergence impact: audit emission lives behind an application-layer contract, so `Domains/Users` extraction (`C2`) carries the audit boundary without transport or infrastructure rework.

## Promoted Quality/Reliability Blocks (`2026-07-03`)

Grounded in the Verified Improvement Intake below; each finding was confirmed against runtime code before promotion.

### Q1 (1-2 days) - Strict Eloquent Runtime Guardrails And Immutable Dates

Verified baseline: `Model::shouldBeStrict()` is not enabled anywhere (`AppServiceProvider::boot()` registers only policies and rate limiters); all `15` model date casts use mutable `'datetime'`; `Date::use(CarbonImmutable::class)` is absent while `AGENTS.md` and new auth code standardize `CarbonImmutable`.

1. Enable `Model::shouldBeStrict(! app()->isProduction())` so lazy loads, silently discarded attributes, and missing-attribute access fail fast in dev/test while production behavior stays unchanged.
2. Fix every violation the full suite surfaces (explicit eager loads, explicit attribute handling); violations are fixed, never silenced or allowlisted.
3. Set `Date::use(CarbonImmutable::class)` and migrate model date casts to `immutable_datetime`; sweep call sites for in-place mutation on model date attributes.

Tests: guardrail asserting strict-mode and immutable-date wiring; the full suite is the regression net for both changes.

DoD: zero strict-mode violations under the full suite; every model datetime attribute is immutable; quality gate green.

Risk/rollback: the cast switch is a runtime semantic change covered by the full suite; revert is a single commit. Strict mode is non-production-only by design.

Convergence impact: strict runtime discipline exposes hidden coupling before module relocation (`C1-C7`) instead of during it.

### Q2 (0.5-1 day) - Supply-Chain Audit Gate

Verified baseline: CI runs no dependency vulnerability checks (`composer audit` / `npm audit` absent from `.github/workflows/ci.yml`); no `.github/dependabot.yml`.

1. Add blocking `composer audit` and `npm audit --omit=dev --audit-level=high` steps to the CI quality gate.
2. Add `.github/dependabot.yml` for `composer`, `npm`, and `github-actions` ecosystems on a weekly cadence.
3. Document the advisory exception policy: a temporarily accepted advisory must be explicit, dated, and carry a removal condition.

DoD: CI fails on known high/critical advisories; automated update PRs flow; README CI section documents the gate.

Risk/rollback: advisory noise can block CI — mitigated by the dated exception policy and weekly dependabot updates reducing drift.

### A2 (1-2 days) - Correlation Propagation Across The Queue Boundary

Verified baseline: `CorrelationIdMiddleware` scopes `X-Correlation-Id` to the HTTP request only; no job or listener carries correlation context; `WebhookProcessingPipeline` logs the provider event id as a correlation fallback, so ingress requests cannot be joined to queued processing, shipment dispatch, or notification logs.

1. Add a correlation accessor boundary in `app/Support/Observability/*` resolving the current correlation id (request attribute in HTTP context; generated stable id in queue/console context).
2. Capture the correlation id into queued job payloads at dispatch for all five jobs (scalar payload discipline preserved) and restore it into structured log context in `handle()`.
3. Webhook enqueue handlers pass the ingress correlation id through the job payload so `webhook.processing_failed` carries the true request correlation instead of the event-id fallback.

Tests: unit coverage for the accessor; feature coverage asserting payload propagation and queued log context; `QueuedJobSafetyGuardrailTest` extended to require the correlation payload key on queued jobs.

DoD: one correlation id joins HTTP ingress -> queue -> side-effect logs across payment, shipping, and notification flows; no new PII in logs.

Convergence impact: observability contracts become module-portable before `Domains/Webhooks`/`Domains/Orders` extraction.

### A1 (2-3 days) - Order Lifecycle Reconciliation And Stuck-State Detection

Verified baseline: webhook receipts are marked `processed_at` inside the ingress transaction while side-effect jobs dispatch `afterCommit()`; a post-commit dispatch failure (queue backend unavailable) or exhausted job retries strand a paid order without confirmation/shipment dispatch. Provider retries are deduplicated by the processed receipt, and the `failed_jobs` table exists but is monitored by nothing — the loss is silent by construction.

1. Add `app:orders-reconcile` following the operational command architecture (options resolver, typed plan, runner, output builder): detect captured payments without a dispatched shipment beyond a config window; pending payments older than a config window; a non-empty `failed_jobs` table.
2. Emit structured `observability.reconciliation` records and integrate detection into the existing alert-check flow; schedule the command per the operational scheduler guardrails (`withoutOverlapping`, cadence intent).
3. Extend the operations runbook with a replay procedure per stuck-state class; side-effect jobs are idempotent by design, so re-dispatch is safe.
4. Record the transactional outbox as the explicit escalation path if reconciliation windows prove insufficient; adopting it requires separate approval and is out of scope for this block.

Tests: unit coverage for plan/threshold resolution; feature matrices for clean state, stuck shipment, stale pending payment, and failed-jobs detection; scheduler wiring guardrail extension.

DoD: every verified silent-loss window has a bounded detection time with an operational alert; the command is orchestration-only; runbook updated.

Convergence impact: order-lifecycle consistency invariants become explicit and observable before `Domains/Orders` extraction (`C5`).

### Q3 (2-4 days) - Psalm Ladder And Scope Parity

Verified baseline: Psalm runs at `errorLevel="6"` and scans only `app` + `database`, while PHPStan runs level `10` across `app/`, `routes/`, and `tests/`.

1. Extend Psalm scope to `routes/`.
2. Measure blockers per level, then raise `6 -> 5 -> 4`, fixing source typing instead of accumulating a baseline (`findUnusedBaselineEntry` stays on).
3. Document the measured blocker set for the next level, following the Wave 23 pattern used for the PHPStan level 7 assessment.

DoD: Psalm level `4` or stricter clean on the extended scope; next-level blockers measured and documented.

### Q4 (1-2 days) - Frontend Type And Test-Signal Hardening

Verified baseline: `tsconfig.json` has `strict: true` but not `noImplicitOverride`, `noFallthroughCasesInSwitch`, or `noUncheckedIndexedAccess`; vitest runs without coverage reporting.

1. Enable `noImplicitOverride` and `noFallthroughCasesInSwitch` (expected low churn) and fix surfaced issues.
2. Measure `noUncheckedIndexedAccess` churn; enable only if the fix surface is bounded, otherwise document the blocker set for a later block.
3. Add v8 coverage reporting to vitest with a non-blocking baseline report in CI; a coverage floor is a separate decision after baseline observation.

DoD: agreed flags enabled with clean `type-check`; coverage visible per CI run; no test behavior changes.

### S1 (4-6 days) - OpenAPI Contract Source Of Truth

Verified baseline: the `/api/v1` contract exists only as duplicated hand-written artifacts — PHP DTO `data/meta` mapping on the backend and TypeScript assertion modules on the frontend; no machine-readable schema exists, so contract drift is caught only by hand-maintained tests on both sides.

Sequence: after `R1` (the stable `error.code` taxonomy must land in the spec); before `C0` (the module API freeze references the spec).

1. Author `docs/api/openapi.yaml` (OpenAPI 3.1) incrementally, starting with auth + catalog + cart routes; formalize the `data`/`meta`/`error` envelope including `error.type` and the `R1` `error.code` taxonomy.
2. Validate the spec in CI (schema lint) and validate real responses against the spec in feature tests for covered routes, so the spec is executable, not decorative.
3. Plan frontend contract-type generation from the spec to retire hand-written assertion drift; the generation switch is a separate follow-up decision per module.

DoD: covered routes fail tests on any response/spec divergence; spec linted in CI; envelope and error taxonomy formalized; no runtime contract changes.

Convergence impact: a machine-readable API freeze protects `/api/v1/*` compatibility through the physical module moves of `C1-C7`.

## Promoted Review Blocks (`2026-06-27`)

### Review Block R1 (3-5 days) - API Error Contract And Stale-Aggregate Failure Taxonomy

Priority: `P1`. Sequence: after `Backlog F3`, before broader Backlog G domain expansion.

1. Extract the API exception-to-response mapping matrix from `bootstrap/app.php` into a dedicated renderer boundary.
2. Introduce an explicit stable error-code taxonomy and add `error.code` to API error responses without removing or changing legacy `error.type`.
3. Replace generic stale-order `DomainException` failures in payment/shipping services with an Orders-owned typed failure.
4. Define context-specific handling:
   - route-model absence remains transport `404`;
   - stale aggregate after a successful bind/load follows an explicitly documented API reliability status;
   - checkout orchestration and queued shipment processing retain retry/failure semantics and are not forced through HTTP status mapping.
5. Add a migration note for eventual `error.type` removal; no removal is allowed in this block.
6. Extend backend contract/architecture guardrails so new public error codes are stable literals and renderer logic does not leak back into controllers.

DoD:

- renderer mapping matrix has isolated unit coverage plus API feature coverage for validation/auth/authorization/domain/not-found/unexpected failures;
- public envelope remains `data/meta/error`, and existing `error.type` assertions remain green;
- payment and shipping stale-order failures are typed and tested at their actual HTTP/orchestration/queue call sites;
- no PHP class name is introduced as a new machine-readable contract.

### Review Refinement R2 (3-5 days) - Exact Promotion Arithmetic And Idempotency Retention

Priority: `P1/P2`. Execution ownership: existing Backlog G items `4/5`, Backlog I2 item `59`, and config-externalization scope.

1. Extend the existing Money work to promotion calculation:
   - preserve promotion values as exact decimal input;
   - replace float percentage transport with an exact rate/value boundary;
   - specify and test half-up rounding for fractional percentages and cent edges;
   - keep JSON numeric output backward-compatible.
2. Make the Promotion enum/value cast boundary statically explicit before narrowing `PromotionType|string`.
3. Add separate checkout config values for:
   - pending idempotency reservation lifetime (currently `30` minutes);
   - completed replay lifetime (currently `24` hours).
4. Add environment examples only for values intended to vary operationally and validate resolved values as positive bounded integers.
5. Preserve request-hash mismatch, cart mismatch, expiry reset, and completed replay behavior under config overrides.

DoD:

- no promotion discount calculation converts a database decimal to `float` before domain arithmetic;
- exact-rate and rounding boundary tests are deterministic;
- both retention windows are independently configurable and covered by tests;
- no existing idempotency or API response contract changes.

### Review Block R3 (1-2 days) - Alert Delivery Outcome Observability

Priority: `P2`. Sequence: independent after Review Block R1.

1. Replace boolean-only alert-channel delivery results with an explicit outcome contract that distinguishes `disabled`, `delivered`, and `failed`.
2. Preserve per-channel warnings for configuration/request failures.
3. Emit one aggregate primary-log event only when at least one enabled delivery was attempted and every attempted channel failed.
4. Preserve cooldown activation only after at least one successful delivery.
5. Cover all-disabled, all-failed, partial-success, full-success, and cooldown-suppressed matrices.

DoD:

- disabled configuration does not create false delivery-failure alerts;
- all attempted delivery failures produce a deterministic aggregate operational signal;
- partial success and cooldown behavior remain backward-compatible.

## Convergence Waves (Modular Monolith Migration)

End-state execution path for the declared `app/Domains/*` target. Each wave is one module slice, requires explicit promotion per the Backlog Intake Rule, and inherits the same invariants: `/api/v1/*` envelope stable, DB schema additive-only, full guardrail and quality-gate coverage per block. No `class_alias` shims: a slice moves atomically with its tests in one block.

Sequencing follows the domain dependency direction (`Catalog -> Cart -> Checkout -> Orders`, with `Payments`/`Webhooks` around order lifecycle) plus `Users` early to capitalize on the F3 auth boundary work.

### Wave C0 (2-3 days) - Module Boundary Foundation

1. Define the module contract convention: a module exposes `Contracts` (interfaces + DTOs) and application handlers as its public API; everything else is module-private.
2. Add a module-boundary guardrail: within `app/Domains/*`, cross-module imports are allowed only from another module's `Contracts` namespace; transport, application, service, and repository layer direction rules apply unchanged inside modules.
3. Decide and document relocation mechanics in `docs/ARCHITECTURE.md` (namespace move per slice, no dual-namespace compatibility shims, provider re-registration policy).
4. Update `docs/REPO_MAP.md` and `docs/DOMAIN_MAP.md` with per-module ownership and the migration state marker per module.

DoD: guardrail active before any runtime code moves; architecture/maps documents define the module contract; no runtime relocation in this wave.

Entry criteria: `R1` and `S1` closed (stable error taxonomy and a machine-readable spec precede the module API freeze).

### Wave C1 (3-5 days) - Catalog Module

Move public catalog read slice into `app/Domains/Catalog`: `CatalogController` + `CatalogIndexRequest`, `Application/Catalog/*`, `CatalogProductReadRepository` (+ contract), catalog cache versioning service. Eloquent models stay shared in `app/Models` until the dedicated model-ownership wave; module code depends on them only through its repository.

DoD: catalog routes serve identical contracts from the module namespace; guardrails updated; old namespaces removed in the same block.

### Wave C2 (3-5 days) - Users/Auth Module

Move auth transport (`Api/V1/Auth/*`), `Application/Auth/*` (issuer, revalidator, audit logger, handlers, contracts), auth repositories, and `EnsureActiveApiUser` wiring into `app/Domains/Users`. Requires `F3-78`/`F3-79` closed so the security contract set moves once, complete.

### Wave C3 (2-4 days) - Cart Module

Move cart transport, `Application/Cart/*`, `Services/Cart/*` (resolver, mutation, mapper), `CartPolicy`, and cart repositories into `app/Domains/Cart`, preserving ownership-guard and locking semantics.

### Wave C4 (3-5 days) - Checkout Module

Move checkout transport, `Application/Checkout/*`, `Services/Checkout/*` collaborators, idempotency guard/config into `app/Domains/Checkout`. Cross-module needs (catalog variants, cart resolution, order writing) go through module contracts defined in C0.

### Wave C5 (3-5 days) - Orders Module

Move order lifecycle: transition policies, `AdminOrderService` order-status flow, account/admin order read models, `OrderInventoryReleaseService`, and status-transition events into `app/Domains/Orders`. The stale-aggregate failure type from `R1` moves here as the Orders-owned failure contract.

### Wave C6 (2-4 days) - Payments Module

Move `Services/Payment/*`, gateway contracts and `Infrastructure/Payments/*`, payment transition policy into `app/Domains/Payments` behind explicit contracts consumed by Checkout/Orders/Webhooks.

### Wave C7 (2-4 days) - Webhooks Module

Move webhook transport, `Application/Webhook/*`, `WebhookProcessingPipeline`, ingress resolvers/appliers, and `Process*WebhookJob` into `app/Domains/Webhooks`. Payment/shipping adapters consume Payments/Orders module contracts only.

Post-C7 follow-up (separate intake): shared model ownership distribution and `app/Services`/`app/Application` directory retirement once every runtime slice lives in a module.

## Verified Improvement Intake (`2026-07-03`)

Internal code-review intake targeting architecture, quality, and evolution headroom beyond the existing backlog. Every finding was verified against runtime code/config before disposition.

1. Strict Eloquent runtime guardrails absent - `confirmed; promoted as Q1`:
   - `Model::shouldBeStrict()` is enabled nowhere; lazy loads, silently discarded attributes, and missing-attribute access pass undetected in dev/test.
2. Mutable date casts contradict the `CarbonImmutable` standard - `confirmed; promoted into Q1`:
   - all `15` model date casts are `'datetime'` (mutable `Carbon`), no `Date::use(...)`, while policy and new auth code standardize `CarbonImmutable`; shared-instance mutation is a latent defect class.
3. Correlation id dies at the queue boundary - `confirmed; promoted as A2`:
   - no job/listener carries correlation context; the webhook pipeline logs the provider event id as a fallback; ingress-to-side-effect trace joining is impossible.
4. Silent side-effect loss window in webhook processing - `confirmed; promoted as A1`:
   - receipts become `processed` inside the ingress transaction while side-effect jobs dispatch `afterCommit()`; dispatch failure or exhausted retries strand paid orders, provider retries are deduplicated, and `failed_jobs` is unmonitored.
5. No supply-chain security gate - `confirmed; promoted as Q2`:
   - CI has no `composer audit`/`npm audit`; no dependabot manifest.
6. Static-analysis asymmetry - `confirmed; promoted as Q3`:
   - PHPStan level `10` over `app/routes/tests` vs Psalm level `6` over `app/database` only.
7. Frontend hardening headroom - `confirmed; promoted as Q4`:
   - `strict: true` without `noImplicitOverride`/`noFallthroughCasesInSwitch`/`noUncheckedIndexedAccess`; no vitest coverage signal.
8. No machine-readable API contract - `confirmed; promoted as S1`:
   - `/api/v1` shape is maintained twice by hand (PHP DTO mapping, TS assertions) with no schema artifact.
9. Production provider enablement is unimplemented product scope - `confirmed; candidate (business decision required)`:
   - `.env.prod.example` ships `PAYMENT_DRIVER=fake-payment`, `SHIPPING_DRIVER=fake-shipping`, `MAIL_MAILER=log`; gateway interfaces exist by design, but real PSP/carrier/transactional-mail integration (including a gateway-initiated refund flow for the existing `refunded` status) needs provider selection and business approval. No engineering block is created until then; the `S1` spec and `C6` module boundary are the technical prerequisites.
10. No browser-level E2E coverage - `acknowledged; candidate only`:
    - API feature tests, smoke commands, and component/composable tests are strong; a thin browser E2E for the checkout happy path is deliberately deferred (infra cost vs current coverage) and creates no block without a concrete gap incident.
11. Backend coverage floor / mutation testing - `acknowledged; candidate only`:
    - revisit after `Q4` coverage baselines exist; CI-time cost must be measured first.
12. Verified healthy (no items created): money columns are `decimal(12,2)` across the schema (the float debt is PHP-boundary only, owned by `R2`); public/admin pagination is capped (`60`/`200`); `.env.prod.example` has `APP_DEBUG=false` with a redis-backed queue/cache/session stack; TypeScript `strict` base is already on.

## Verified Review Intake (`2026-06-27`)

This intake records a code-verified external review. It is promoted into the active roadmap through blocks `R1/R2/R3`, and it does not displace the locked `Backlog F3` security sequence.

1. Payment/shipping stale-order failure taxonomy - `confirmed with status-semantics correction`:
   - `PaymentService` and `ShippingService` still throw generic `DomainException` when a previously supplied order cannot be reloaded under lock;
   - a normal missing payment route is already handled by route-model binding before service execution, while shipping runs from `DispatchShipmentJob` and has no HTTP response contract;
   - implementation must introduce a typed Orders-owned stale-aggregate failure and define mapping by call-site context instead of globally converting every occurrence to HTTP `404`;
   - service, HTTP feature, checkout-orchestration, and queued-job failure behavior require deterministic coverage.
2. Public API exception identity and renderer boundary - `confirmed with backward-compatibility correction`:
   - `bootstrap/app.php` exposes `class_basename($exception)` as `error.type`, and `CatalogTest` asserts that field, so it is already part of the observable API contract;
   - extract the mapping matrix into a dedicated API exception renderer for isolated unit coverage while retaining end-to-end feature coverage;
   - introduce a stable machine-readable `error.code` additively; preserve `error.type` until an explicit deprecation/migration plan approves removal;
   - stable codes must come from an explicit taxonomy and must not be derived from PHP class names.
3. Promotion discount arithmetic - `confirmed and folded into existing Backlog G items 4/5 plus Backlog I2 item 59`:
   - `CheckoutDiscountResolver` converts the decimal Eloquent value to `float`, and `Money::percentage()` also accepts `float`;
   - fixed and percentage promotions must cross the domain boundary as exact decimal/rate values with explicit half-up rounding tests;
   - `PromotionType|string` normalization may be removed only after the Eloquent cast boundary is made statically explicit; do not trade runtime compatibility for a narrower signature without a typed mapper.
4. Checkout idempotency retention configuration - `confirmed with two-window correction`:
   - pending reservation lifetime is hardcoded to `30` minutes in `CheckoutIdempotencyGuard`;
   - completed replay lifetime is separately hardcoded to `24` hours in `CheckoutOrderFinalizer`;
   - both values require distinct validated config keys, environment examples where operational tuning is intended, and tests proving config overrides preserve replay/mismatch semantics.
5. Observability alert delivery failure - `partially confirmed`:
   - real email/Slack/PagerDuty channels already emit per-channel routing warnings for configuration and delivery failures;
   - the router lacks an aggregate all-attempted-channels-failed signal, but `sent === []` alone cannot distinguish disabled channels from failed delivery;
   - evolve the channel result contract to explicit `disabled` / `delivered` / `failed` outcomes, emit the aggregate warning only when at least one enabled delivery was attempted and all attempts failed, and preserve cooldown only after a successful delivery.
6. Frontend god-composables - `not confirmed; no implementation block`:
   - reviewed account, catalog, auth, and checkout composables are focused, page components remain orchestration-only, and critical composables have dedicated tests;
   - line count alone is not a refactoring trigger; reopen only with concrete evidence of mixed query/mutation/view-model responsibility, duplicated behavior, or an untestable side effect.

## Locked Constraints

1. Keep `/api/v1/*` envelope backward-compatible (`data/meta/error`).
2. Internal contracts may evolve to typed DTO/value objects.
3. Controller layer remains transport-only.
4. One logical block = one coherent commit-sized change.
5. No silent architecture tradeoffs; all exceptions must be explicit and reversible.
6. Depth is mandatory for every block:
   - extract logic into explicit boundary class/service/policy,
   - add deterministic tests for transition/rule matrices,
   - update the execution log with checks run.

## Program Posture

1. This roadmap is a strengthening program, not a rescue rewrite.
2. Completed waves are presumed correct unless concrete regression evidence appears.
3. New waves must preserve current strong patterns and extend them into lagging layers.
4. Simplification that removes explicit boundaries, DTOs, handlers, orchestration shells, or guardrails is out of scope.

## Non-Goals

1. Do not reopen completed CQRS, controller-purity, webhook, or smoke-command refactors without explicit defect evidence.
2. Do not replace typed DTO boundaries with ad-hoc arrays, resources, or ORM leakage.
3. Do not merge account, admin, and catalog contexts into shared repositories unless the abstraction is demonstrably context-neutral.
4. Do not reintroduce page-local frontend query, formatting, or mutation helpers where shared primitives already exist.
5. Do not start convergence waves before their entry criteria; module extraction under an unstable error/security contract multiplies churn.

## Interface/Contract Changes

Completed (see registry and execution log): typed webhook payload boundaries; `*FilterDto` migration; enum-based admin order status input; DTO result boundaries for all handlers; async shipping ingestion parity; additive canonical account routes; admin selector endpoints; cleanup config/indexes; `CatalogIndexRequest`; `ResolvesAuthenticatedUser`; canonical `formatPrice`; bounded read repositories + summary projector; finite Sanctum token expiration with active-user revalidation and split revoke semantics; shared password policy, identity-aware login limiter, and timing-parity credential verification; structured auth security audit trail (`AuthAuditLogger` contract, stable `auth.audit.*` event taxonomy, whitelisted context with `sha256` email hash on failure paths); strict Eloquent runtime mode in non-production environments, `CarbonImmutable` global resolver, and immutable model date casts across all models.

Pending, owned by queued blocks:

1. `R1`: additive stable `error.code` through a dedicated renderer; Orders-owned stale-aggregate failure with context-specific handling; `error.type` preserved until an approved deprecation migration.
2. `R2`: exact decimal/rate promotion boundary; separate validated pending/completed idempotency retention config.
3. `R3`: typed alert-channel delivery outcomes (`disabled`/`delivered`/`failed`).
4. `Q2`: CI gains blocking dependency-audit steps; dependabot manifest added.
5. `A2`: queued job payloads gain a scalar `correlation_id` key restored into log context.
6. `A1`: new `app:orders-reconcile` command with validated `reconciliation.*` config windows and scheduler registration.
7. `S1`: `docs/api/openapi.yaml` becomes the machine-readable `/api/v1` contract, validated in CI and feature tests.
8. `C0-C7`: module public-API convention under `app/Domains/*` with cross-module imports restricted to `Contracts` namespaces.

## Risk Register

| Risk | Owner block | Mitigation |
| --- | --- | --- |
| Audit trail leaks PII/secrets into logs | F3-79 | Context-key whitelist with a dedicated leak test; email hashed on failure paths |
| New `error.code` taxonomy churns into a second unstable contract | R1 | Codes are literal constants from one taxonomy; additive-only; feature matrix locks values |
| Exact-decimal migration changes computed totals | R2 | Half-up rounding fixed by tests on cent edges; JSON output asserted byte-compatible for existing fixtures |
| Idempotency window misconfiguration breaks replay semantics | R2 | Bounded positive-int validation; override tests prove replay/mismatch behavior per window |
| Module relocation conflicts with parallel feature work | C1-C7 | One module per block; atomic move with tests; no dual namespaces; entry criteria gate the start |
| Guardrail erosion during moves (allowlist growth) | C0-C7 | `AGENTS.md` shrink-only allowlist rule; module-boundary guardrail lands before first move (C0) |
| Dependency-advisory noise blocks CI | Q2 | Dated exception policy with removal conditions; weekly dependabot reduces drift |
| Reconciliation false positives page on-call | A1 | Config-driven detection windows; alerts flow through the existing cooldown router |
| `noUncheckedIndexedAccess` churn explodes frontend diff | Q4 | Measure first; enable only with a bounded fix surface, otherwise document blockers |
| Spec becomes decorative and drifts from runtime | S1 | Spec is CI-executable: schema lint plus response validation in feature tests |

## Program Exit Targets

Achieved (mechanically verified):

1. No mixed-context repositories across account/admin/catalog read paths — `RepositoryReadBoundaryTest`, Wave 18.
2. No inline validation in API V1 controllers — `ApiControllerValidationBoundaryTest`, Wave 21.
3. Centralized authenticated-user resolution — `ApiControllerAuthenticatedUserBoundaryTest`.
4. Category selectors decoupled from management-list contracts — Wave 16 coverage.
5. Account orders summary/detail read-model parity — Wave 15 coverage.
6. Maintenance cleanup with explicit resource strategy and bounded execution — Wave 17 coverage.
7. Single canonical frontend price formatting — Wave 22 coverage.
8. PHPStan level 10 clean without a baseline file — `composer run analyse`.
9. Guardrails cover controllers, handlers, repositories, queued side effects, and policy completeness — Wave 24 suite.
10. Finite token lifetime, active-user revalidation, and split revoke semantics — `AuthTokenLifecycleGuardrailTest`, `AuthFlowTest`, `PasswordResetFlowTest`.
11. Credential policy, identity-aware lockout, and timing parity — `AuthCredentialHardeningGuardrailTest`, `AuthFlowTest`, `PasswordResetFlowTest`.
12. Structured auth security audit trail wired into observability — `AuthAuditTrailFeatureTest`, `AuthAuditEmissionGuardrailTest`, `AuthAuditEventTest`, `ObservabilityAuthAuditLoggerTest` (`F3-79`).
13. Strict Eloquent mode enforced in non-production; all model date attributes immutable — `StrictEloquentAndImmutableDatesGuardrailTest`, full suite (`Q1`).

Remaining (each verified by its owning block's DoD):

14. Stable additive `error.code` through a dedicated renderer; typed stale-aggregate failures across HTTP/orchestration/queue call sites — `R1`.
15. Exact promotion arithmetic to the JSON boundary; independently configurable idempotency windows — `R2`.
16. Alert routing distinguishes disabled channels from attempted-delivery failures with aggregate all-failed signal — `R3`.
17. CI blocks known high/critical dependency advisories; automated update PRs active — `Q2`.
18. One correlation id joins HTTP ingress, queued processing, and side-effect logs — `A2`.
19. Every silent side-effect-loss window has a bounded, alerting detection time; `failed_jobs` is monitored — `A1`.
20. Psalm level `4` or stricter clean on extended scope — `Q3`.
21. Frontend hardening flags enabled; per-run coverage signal visible in CI — `Q4`.
22. Covered `/api/v1` routes are validated against a machine-readable spec in CI — `S1`.
23. Module-boundary guardrail active and first module slices (Catalog, Users) serving production traffic from `app/Domains/*` — `C0-C2`.

## Backlog Intake Rule

1. `docs/DEEP_ARCHITECTURE_AUDIT_2026_03.md` and `docs/DEEP_ARCHITECTURE_AUDIT_2026_03_V2.md` are aligned backlog inputs, not active execution authority.
2. Audit findings remain candidate backlog until explicitly promoted into this file as waves or blocks.
3. Promotion preserves the architecture-first sequence: safety and locking; backend boundary quick wins; frontend consistency; deep domain expansion; platform enablement.
4. Deep domain items (`Money` completion, `app/Domain` expansion, checkout orchestrator growth, domain-event rollout) require separate approval and must not be bundled into quick-win slices.
5. Security promotion order inside the v2 intake is fixed: token/session lifecycle (`77`, closed); credential hardening (`78`, closed); auth audit trail (`79`, active); mass-assignment surface + transport security baseline (`80`, `81`); data-at-rest minimization + security guardrails (`82`, `83`).
6. The `2026-06-27` external review is promoted only through `R1`/`R2`/`R3`; findings already covered by Backlog G/I2 are scope refinements, not duplicate items.
7. The `2026-07-03` internal code review is promoted only through `Q1-Q4`, `A1`/`A2`, and `S1`; its remaining findings (provider enablement, browser E2E, coverage floor) stay candidates until explicitly promoted, and provider enablement additionally requires a business decision.
8. A size/complexity hypothesis creates no work item without a concrete boundary violation, duplicated behavior, race, or untestable side effect.
9. Every promoted block must declare its modular-monolith convergence impact.

## Mandatory Test Matrix

1. Architecture guardrails (enforced now):
   - full API V1 controller boundary coverage; no ORM/paginator returns from handlers; no inline `$request->validate()`; repository business-decision and status-interpretation bans; jobs/listeners afterCommit discipline; policy completeness matrix; token lifecycle and credential-hardening contracts; no repository-level audit logging (`F3-79`); strict-mode and immutable-date wiring (`Q1`); documentation authority and map governance.
   - added by queued blocks: correlation payload key on queued jobs (`A2`); dedicated renderer ownership with literal error-code taxonomy (`R1`); reconciliation scheduler wiring (`A1`); module cross-import restriction to `Contracts` (`C0`).
2. Feature tests:
   - webhook parity and idempotency; admin status transition validation; account order contract parity; payload hash mismatch and signature failures; finite/expired token behavior, inactive-user revalidation, current-token logout, password-reset global revoke; weak-password matrix, email+IP lockout, and known/unknown-email envelope parity.
   - added by queued blocks: audit-record presence per auth flow (`F3-79`); correlation propagation through queued flows (`A2`); `error.code` + legacy `error.type` compatibility matrix, stale-order transport behavior (`R1`); config-driven retention override semantics (`R2`); reconciliation detection matrices (`A1`); spec-validation of covered routes (`S1`).
3. Unit tests:
   - transition policies; checkout/cart collaborators; observability modules; cleanup strategy; summary projection; shared format utility; password-policy composition, limiter-key derivation, and dummy-hash verification.
   - added by queued blocks: audit context whitelist (`F3-79`); renderer status/code/type matrix and typed stale failures (`R1`); exact-rate rounding and retention config (`R2`); channel outcome and aggregate-failure matrices (`R3`).
4. Frontend tests: route-query schema helpers; composable race/cancellation guarantees; API contract assertions; account lazy detail loading; shared category options; catalog/checkout/auth composable coverage.
5. Smoke: `app:api-contract-smoke` includes shipping webhook contract; `app:webhook-flow-smoke` stays green with idempotent replay.

## Quality Gate

The canonical sequential gate, scope rules, and route-smoke requirements are defined once in `AGENTS.md` ("Quality gate (production readiness)"). Every block in this plan closes only with that gate green and the executed checks recorded in `docs/REFACTORING_EXECUTION_PLAN.md`.

## Assumptions and Defaults

1. Priority mode is `Architecture-first`.
2. Public API envelope must remain stable; additive endpoints/read models are allowed.
3. Any webhook status-code normalization is an explicit later migration.
4. No block is complete until the full quality gate is green and logged.
5. Legacy account aliases remain until a separate approved deprecation/removal plan exists.
6. Estimates are calendar-day effort classes for a single focused engineer and do not override sequencing.

## Plan Change Control

This file changes only when a block is promoted, closed, or re-scoped; every revision updates the `Last revised` header date.

| Date | Change |
| --- | --- |
| `2026-03-05` | Program created; waves 0-24 defined |
| `2026-06-27` | External review intake verified; `R1/R2/R3` promoted; frontend hypothesis rejected |
| `2026-06-28` | `F3-77` closed with follow-up fixes and approved `A1` breaking change |
| `2026-07-03` | Plan restructured: completed-work narrative moved to status registry (details in execution log); `F3-78`/`F3-79` given execution definitions; convergence waves `C0-C7` defined; execution queue, risk register, and verifiable exit targets added; quality gate deduplicated to `AGENTS.md` |
| `2026-07-03` | Verified improvement intake: seven blocks promoted (`Q1` strict runtime + immutable dates, `Q2` supply-chain gate, `A2` queue correlation, `A1` lifecycle reconciliation, `Q3` Psalm ladder, `Q4` frontend hardening, `S1` OpenAPI contract); provider enablement, browser E2E, and coverage floor recorded as candidates; queue resequenced with rationale |
| `2026-07-03` | `F3-78` closed: shared password policy, identity-aware login limiter, timing-parity verification, and deterministic coverage; `F3-79` is active next |
| `2026-07-03` | `F3-79` closed: `AuthAuditLogger` contract, stable `auth.audit.*` taxonomy, whitelisted context with `sha256` email hash on failure paths, repository-persistence-only guardrail, and deterministic coverage; `Q1` is active next |
| `2026-07-03` | `Q1` closed: `Model::shouldBeStrict(! production)` wired in `AppServiceProvider::boot()`; strict-mode mass-assignment violations fixed at source (no allowlist) across webhook receipt update and smoke user factories; `Date::use(CarbonImmutable::class)` and all 16 model date casts migrated to `immutable_datetime`; `StrictEloquentAndImmutableDatesGuardrailTest` enforces wiring and forbids mutable casts; `Q2` is active next |
