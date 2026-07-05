# Architecture Refactor Next (Architecture-First)

Program start: `2026-03-05`
Last revised: `2026-07-05` (`C2` closure)
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
- Verified-intake blocks `Q1` (strict Eloquent runtime guardrails + immutable dates), `Q2` (supply-chain audit gate), `A2` (correlation propagation across the queue boundary), `R2` (exact promotion arithmetic and idempotency retention), and `R3` (alert delivery outcome observability) are closed; `R1` (API error contract and stale-aggregate taxonomy) is closed.
- The active block is the convergence wave `C1` (Catalog Module); `C1` is closed (`2026-07-05`), and the convergence waves `C2-C7` are the remaining roadmap surface, each requiring promotion before activation.
- A verified code-review intake (`2026-07-03`) promoted seven quality/reliability blocks: strict runtime guardrails (`Q1`), supply-chain audit gate (`Q2`), queue correlation propagation (`A2`), order-lifecycle reconciliation (`A1`), Psalm ladder (`Q3`), frontend hardening (`Q4`), and an OpenAPI contract source (`S1`).
- The end-state direction is physical convergence to `app/Domains/*`, defined by Convergence Waves `C0-C7`. `C0` (foundation) and `C1` (Catalog module) are closed (`2026-07-05`); `C2-C7` (slice moves) remain pending promotion.

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
| Q2 | Verified intake (`2026-07-03`) | Blocking `composer audit` and `npm audit --omit=dev --audit-level=high` steps in the CI quality gate; `.github/dependabot.yml` scheduling weekly update PRs for composer/npm/github-actions; README documents the audit gate and the dated advisory exception policy; `SupplyChainAuditGateGuardrailTest` enforces the contract |

## Execution Queue

Locked order. A block starts only when the previous one is closed in the execution log (exception: `R3` may run any time after `R1`).

| # | Block | Priority | State |
| --- | --- | --- | --- |
| 1 | `F3-79` Auth security audit trail | P1 | **Closed** |
| 2 | `Q1` Strict Eloquent runtime guardrails and immutable dates | P1 | **Closed** |
| 3 | `Q2` Supply-chain audit gate (CI + dependabot) | P1 | **Closed** |
| 4 | `A2` Correlation propagation across the queue boundary | P1 | **Closed** |
| 5 | `R1` API error contract and stale-aggregate taxonomy | P1 | **Closed** (`2026-07-04`) |
| 6 | `R2` Exact promotion arithmetic and idempotency retention (with Backlog G items `4/5`, I2 item `59`) | P1/P2 | **Closed** (`2026-07-04`) |
| 7 | `R3` Alert delivery outcome observability | P2 | **Closed** (`2026-07-04`) |
| 8 | `A1` Order lifecycle reconciliation and stuck-state detection | P1 | **Closed** (`2026-07-04`) |
| 9 | Security intake items `80/81` (mass-assignment surface, transport security baseline) | P1 | **Closed** (`2026-07-04`) |
| 10 | Security intake items `82/83` (data-at-rest minimization, security guardrails) | P2 | **Closed** (`2026-07-04`) |
| 11 | `Q3` Psalm ladder and scope parity | P2 | **Closed** (`2026-07-04`) |
| 12 | `Q4` Frontend type/test hardening | P2 | **Closed** (`2026-07-04`) |
| 13 | `S1` OpenAPI contract source of truth | P1 | **Closed** (`2026-07-04`) |
| 14 | Convergence wave `C0` (module boundary foundation) | P1 | **Closed** (`2026-07-05`) |
| 15 | Convergence wave `C1` (Catalog module slice move) | P1 | **Closed** (`2026-07-05`) |
| 16 | Convergence waves `C2-C7` (remaining modular monolith migration slices) | P1 | Defined below — each wave requires promotion |

Sequencing rationale: `Q1`/`Q2`/`A2` are small guardrail-first blocks placed after the locked `F3` sequence and before `R1` — they tighten regression detection for every later block and do not touch the fixed security-intake order. `A1` precedes the `80/81` promotion decision because it closes a verified customer-impacting silent-loss window. `S1` lands after `R1` (the `error.code` taxonomy belongs in the spec) and before `C0` (module API freeze references the spec).

Remaining audit v1/v2 backlog not listed here stays candidate-only per the Backlog Intake Rule.

## Active Block Definitions

### 80/81 (1-2 days) - Mass-Assignment Surface And Transport Security Baseline

Problem: privilege/state fields (`User.is_active`, `Order.status/payment_status/shipment_status`, `Payment.status`, `Shipment.status`) were mass-assignable, allowing a future mapping/controller/service mistake to escalate into privilege/state corruption; and transport security (CORS allowlist, secure-cookie default, proxy-aware HTTPS enforcement) was enforced only by deployment convention, with no versioned source of truth.

Closed (`2026-07-04`):

1. Removed privilege/state fields from `$fillable` on `User`, `Order`, `Payment`, `Shipment`. Legitimate transition paths (`AdminOrderService::updateStatus`, `PaymentWebhookTransitionApplier`, `ShippingWebhookTransitionApplier`, `CheckoutOrderWriter`, `PaymentService`, `ShippingService`) migrated to explicit `forceFill([...])->save()`. Factories continue to work through Laravel's internal `Model::unguarded()`.
2. Added `config/cors.php` (env-driven allowlist via `CORS_ALLOWED_ORIGINS`, scoped to `api/*`, credentials disabled) and `config/security.php` (`force_https`, `trusted_proxies`, `trusted_hosts` resolved from env).
3. Added `app/Http/Middleware/ForceHttpsMiddleware.php`: redirects HTTP→HTTPS with status 301 when `APP_ENV != local` and `APP_FORCE_HTTPS=true`; respects `X-Forwarded-Proto: https` to prevent redirect loops behind proxies.
4. Registered `ForceHttpsMiddleware` globally in `bootstrap/app.php`.
5. `config/session.php` secure-cookie default changed from bare `env('SESSION_SECURE_COOKIE')` to `env('SESSION_SECURE_COOKIE', env('APP_ENV', 'production') !== 'local')` — defaults secure cookies in non-local without requiring explicit deployment config.
6. Five env keys documented across `.env.example`, `.env.stage.example`, `.env.prod.example`, `.env.testing`: `SESSION_SECURE_COOKIE`, `CORS_ALLOWED_ORIGINS`, `APP_FORCE_HTTPS`, `APP_TRUSTED_PROXIES`, `APP_TRUSTED_HOSTS`.

Tests:

- unit/architecture: `SensitiveStateFillableGuardrailTest` (six privilege/state fields locked out of `$fillable`), `SensitiveFieldsRejectMassAssignmentTest` (`MassAssignmentException` on direct `fill()`), `TransportSecurityBaselineGuardrailTest` (file-based invariants for CORS, security config, session secure default, middleware class, bootstrap registration).
- feature: `HttpsEnforcementTest` (301 redirect in non-local + force_https; no-op in local; forwarded-proto honored); existing webhook/admin transition suites (`PaymentWebhookTest`, `ShippingWebhookTest`, `PhaseOneHardeningTest`) verify `forceFill+save` migrations preserve behavior.

Convergence impact: the mass-assignment invariant becomes structural, so `Domains/Users` and `Domains/Orders` extraction (`C2`) carries the boundary without revisiting `$fillable` policy. Transport security baseline is versioned, so any future deployment inherits sane CORS/HTTPS/secure-cookie defaults and module extraction does not need to revisit transport policy per module.

### 82/83 (1-2 days) - Data-At-Rest Minimization And Security Guardrail Rollup

Problem: address and provider payload JSON columns are stored as plaintext without an explicit data-classification inventory or a defensive key boundary (audit item 82); security invariants (finite Sanctum TTL, `active.api.user` revalidation, login limiter, secure-cookie default, force-https gate, CORS scope) are enforced only by point guardrails scattered across the suite, with no single rollup that fails one obvious gate when any invariant drifts (audit item 83).

Closed (`2026-07-04`):

1. Address payload boundary locked: `CheckoutAddressInputDto::toArray()` emits exactly the allowlisted shape `{line1, city, country, postcode}`; `AddressPayloadBoundaryGuardrailTest` scans every `billing_address`/`shipping_address` blob construction site under `app/` (DTO, request, checkout writer, three smoke factories) and rejects phone/email/notes/recipient_name/card/cvv drift.
2. Gateway payload boundary locked: `GatewayPayloadBoundaryGuardrailTest` forbids PII literals (`card`, `card_number`, `pan`, `cvv`, `cvc`, `ssn`, `password`, `recipient_name`) in `FakePaymentGateway` and `FakeShippingGateway`, and requires payload construction through `JsonPayload::fromArray()`. The boundary is ready for future real-provider adapters.
3. `docs/SECURITY_DATA_CLASSIFICATION.md` records the inventory of PII-bearing columns (`users.email/phone/password`, `orders.email/billing_address/shipping_address`, `payments.payload`, `shipments.payload`), the allowed key sets for each JSON column, the plaintext-at-rest threat model, and the field-level-encryption follow-up prerequisites; `SecurityDataClassificationDocGuardrailTest` prevents silent drift of the inventory.
4. Unified `SecurityConfigGuardrailTest` aggregates the cross-cutting security-config contract in one place: finite Sanctum expiration, bounded login-throttle config, `session.secure` boolean resolution + non-local default `true`, `security.force_https` boolean, non-empty `security.trusted_proxies`, CORS list shape with `supports_credentials=false` and `api/*`-scoped paths, login route `throttle:auth.login` limiter, `active.api.user` middleware alias, and `auth:sanctum` → `active.api.user` route coverage. The point guardrails remain the per-contract authority; this rollup is the cross-cutting canary.

Out of scope (deliberate): field-level encryption / encrypted casts on address or provider payloads; backfill migrations rewriting existing rows; changes to the `/api/v1/*` response envelope. The encryption follow-up is recorded as a roadmap candidate with prerequisites (key management, backfill, query-shape impact, rollback path) satisfied by this block's boundary work.

Tests:

- unit/architecture: `AddressPayloadBoundaryGuardrailTest` (8 cases — DTO shape, return-type contract, six construction sites), `GatewayPayloadBoundaryGuardrailTest` (5 cases — PII-literal ban, JsonPayload routing, contract location), `SecurityDataClassificationDocGuardrailTest` (5 cases — doc existence, PII columns, allowlist keys, encryption follow-up, enforcing-guardrail references), `SecurityConfigGuardrailTest` (12 cases — unified rollup).

Convergence impact: the data-classification inventory and the closed-shape JSON contracts give the modular-monolith migration (`C2`, `C5`) a stable surface — module relocation carries the boundary without revisiting persistence policy. The unified security-config rollup becomes the single canary for cross-cutting security drift, so module extraction cannot silently break transport or auth wiring.

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

### A2 (1-2 days) - Correlation Propagation Across The Queue Boundary — CLOSED

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

**Closed (`2026-07-04`)**: `app:orders-reconcile` ships with three independent detectors (`StuckShipmentDetector`, `StalePendingPaymentDetector`, `FailedJobsDetector`) wired through `OrdersReconcileOptionsResolver` → `OrdersReconcileRunner` → `OrdersReconcileOutputBuilder`, mirroring the operational command architecture. Each threshold is a bounded positive integer in `config/orders.php` (`reconciliation.stuck_shipment_minutes`, `reconciliation.stale_pending_payment_minutes`, `reconciliation.failed_jobs_threshold`); the schedule is gated by `APP_ORDERS_RECONCILE_ENABLED` and `APP_ORDERS_RECONCILE_CRON`. Every run emits a structured `observability.reconciliation` log record with per-detector counts, and `--route-alerts` reuses the existing `ObservabilityAlertRouter` channel infrastructure for delivery. The `OperationalSchedulerWiringGuardrailTest` was extended to assert the schedule; the dedicated runbook lives at `docs/OPERATIONS_RUNBOOK_ORDER_RECONCILIATION.md`. Coverage: resolver/detector/output unit suites plus a feature matrix (clean, stuck shipment, stale pending payment, failed_jobs, invalid option, paid-with-shipped-shipment). Quality gate: lint/analyse/test green.

### Q3 (2-4 days) - Psalm Ladder And Scope Parity — Closed (`2026-07-04`)

Priority: `P2`. Sequence: after `82/83` (security intake), before `Q4` (frontend hardening).

**Status: closed on `2026-07-04`.** Delivered:

- `psalm/plugin-laravel` registered in `psalm.xml` (`Psalm\LaravelPlugin\Plugin`) for Laravel-aware Eloquent type inference, narrowing the divergence with larastan/PHPStan that previously produced mass `UndefinedMagicPropertyFetch` findings.
- Psalm scope extended to `routes/`, matching PHPStan's scope for the production surface (`app/`, `routes/`, `database/factories`, `database/seeders`).
- `errorLevel` raised `6 → 5 → 4`. Level 5 fix: `AppServiceProvider::boot` migrated from `$this->app->isProduction()` (concrete-only method on `Illuminate\Foundation\Application`) to `$this->app->environment('production')` (declared on the `Illuminate\Contracts\Foundation\Application` interface), so the contract holds for any container implementation.
- Level 4 source-typing fixes (no baseline accumulation): `RedundantCast` removed from `WebhookProcessingPipeline`, `MaintenanceCleanupExecutor`, `MaintenanceCleanupRetentionResolver`, `OrdersReconcileRunner`; `now()->timestamp` migrated to `now()->getTimestamp()` in `ObservabilityAlertCooldownStore` (interface-declared accessor); repository `@return` shapes tightened in `AdminProductReadRepository` and `CatalogProductReadRepository` (callable union types instead of unparseable `Closure(Relation<*, *, *>): mixed`); `Promotion` model annotated with full `@property` inventory (id/name/code/usage_limit/usage_count/starts_at/ends_at/created_at/updated_at); `ApiContractSmokeContextFactory` narrowed `firstOrCreate` result with `assert($user instanceof User)`.
- Documented plugin-version tradeoff: psalm/plugin-laravel v3.0.x (the only line compatible with the OSPanel-managed PHP 8.4.1 runtime and the pinned Psalm 6.4.1) types `LengthAwarePaginator<TValue>` and Eloquent relations as `<TRelatedModel>` (1 template-param, matching Laravel upstream); larastan types them as `<TKey, TValue>` / `<TRelatedModel, TDeclaringModel>` (2 template-params, matching the project-side PHPDoc). Switching the project annotations to 1-parameter form would break PHPStan parity; the divergence is closed upstream in plugin v3.14 (PR #1082) and v3.14.2 (PR #1141), which require PHP ~8.4.3 and Psalm ^6.16.1. Until the runtime is upgraded, `TooManyTemplateParams` (4 directories/files) and `InvalidDocblock` (`app/Models`) are suppressed via `psalm.xml` `issueHandlers` with enumerable scope and documented rationale, removable in a single edit once the plugin is upgraded.
- Guardrail: `PsalmLadderScopeParityGuardrailTest` (8 assertions) locks `errorLevel ≤ 4`, the extended scope, plugin registration, baseline-free progression, `findUnusedBaselineEntry=true`, the documented template-arity suppressions, the composer constraint window, and the `AppServiceProvider` `environment()` contract.

Convergence impact: Psalm level 4 on `app/` + `routes/` + `database/` closes the static-analysis asymmetry between Psalm and PHPStan called out in the risk register (#6) and unblocks future strict-runtime tightening under the same toolchain. The plugin-version tradeoff is the single tracked follow-up (see risk register #22 upgrade candidate).

DoD: Psalm level `4` or stricter clean on the extended scope; next-level blockers measured and documented.

### Q4 (1-2 days) - Frontend Type And Test-Signal Hardening — Closed (`2026-07-04`)

Priority: `P2`. Sequence: after `Q3` (Psalm ladder), before `S1` (OpenAPI contract).

**Status: closed on `2026-07-04`.** Delivered:

- `tsconfig.json` extended with `noImplicitOverride` (forces `override` keyword on inherited members) and `noFallthroughCasesInSwitch` (forbids fall-through switch cases). Both surfaced zero churn — the existing Vue 3 + TypeScript codebase already follows the discipline.
- `noUncheckedIndexedAccess` measured (Slice 2): 55 errors total. Distribution: **2 production errors** (`AdminProductVariantsSection.vue:17`, `useAdminOrderDetailsState.ts:44`) + **53 test-only errors** concentrated in `tests/composables/use-admin-mutation-flows.spec.ts` (21), `tests/components/admin/admin-component-contracts.spec.ts` (11), and five other test files. Per Q4 DoD ("enable only if the fix surface is bounded"), the flag was **deferred**: the 2 production fixes do not justify 53 test-only fixes that risk introducing `!` assertions or `as` casts around test-fixture convenience. The blocker set is documented in the plan and tracked in risk register #23 for a dedicated `Q4-followup` that first tightens test fixtures to typed factories.
- V8 coverage reporting added: `@vitest/coverage-v8 ^4.1.9` (aligned with `vitest ^4.0.18`); `vitest.config.ts` extended with `coverage: { provider: "v8", reporter: ["text", "html"], reportsDirectory: "coverage/", all: true }`; `package.json` `test:coverage` script (`vitest run --coverage`) added; `.gitignore` excludes `/coverage`. The default `test` script stays `vitest run` (no implicit coverage in the quality gate — coverage is opt-in via `test:coverage`).
- Baseline coverage observed: 86.96% statements, 75.35% branches, 86.36% functions, 88.18% lines (2609 statements across the `resources/js` surface). No coverage floor is introduced in this block; that is a separate decision after baseline observation.
- Guardrail: `FrontendTypeAndTestSignalGuardrailTest` (5 tests, 17 assertions) locks `strict: true` + `noImplicitOverride` + `noFallthroughCasesInSwitch`, asserts `noUncheckedIndexedAccess` stays deferred (flips to present when the follow-up lands), enforces v8 coverage provider + html/text reporters + `coverage/` directory + `all: true`, requires `test:coverage` script and `@vitest/coverage-v8` in devDependencies, and excludes `/coverage` from git.

Convergence impact: the strict flags and coverage baseline catch type drift and surface test-signal regressions before they reach the convergence waves (`C0-C7`), where the frontend contract layer will be reshuffled. The deferred `noUncheckedIndexedAccess` is the single tracked follow-up (risk register #23).

DoD: agreed flags enabled with clean `type-check`; coverage visible per `npm run test:coverage`; no test behavior changes.

### S1 (4-6 days) - OpenAPI Contract Source Of Truth — Closed (`2026-07-04`)

Priority: `P1`. Sequence: after `R1` (closed; the stable `error.code` taxonomy landed in the spec); before `C0` (the module API freeze references the spec).

**Status: closed on `2026-07-04`.** Delivered:

- `docs/api/openapi.yaml` authored as a single OpenAPI **3.0.3** document (downgraded from the roadmap's "3.1" because `cebe/php-openapi` is OpenAPI-3.0-only on stable releases; 3.1 support sits in an unmerged PR since 2021 and no other stable PHP validator supports it on the Symfony YAML v8 stack that Laravel 12 ships). The spec formalizes the three top-level envelopes (`{data}`, `{data,meta}`, `{error}`), the closed 9-member `ApiErrorCode` enum, and the two distinct error shapes:
  - **Shape A (`ErrorResponseController`)** — controller-caught `AuthApplicationException` on login/register/forgot/reset/verify: `{error:{message, request_id?, type}}`, no `code`, no `validation`.
  - **Shape B (`ErrorResponseRenderer`)** — `ApiExceptionRenderer`-emitted on everything else: `{error:{message, request_id?, type, code, validation?}}` with `validation` only on 422.
- Spec coverage: 14 in-scope paths across auth (8: register/login/logout/me/profile/forgot-password/reset-password/email verify/email verification-notification), catalog (3: products list, product show by slug, categories list), cart (3: show, upsert item, remove item). Component schemas (`AuthUser`, `AuthToken`, `CatalogProduct`, `CatalogProductVariant`, `CatalogCategory`, `Cart`, `CartItem`, `CartSummary`, `PaginationMeta`) built verbatim from the `*ResultDto::toArray()` outputs (no JsonResource classes exist for these domains per ADR-0002).
- Tooling: `devizzent/cebe-php-openapi ^1.1.5` added to `require-dev` — the actively maintained fork of `cebe/php-openapi` that supports both OpenAPI 3.0 and 3.1 and, critically, the `symfony/yaml ^3-8` constraint window required by Laravel 12 (the original `cebe/php-openapi 1.8.0` caps at `symfony/yaml ^7` and is uninstallable in this project). Drop-in compatible (same `cebe\openapi\*` namespace, declares `replace: cebe/php-openapi`).
- Validation infrastructure: `tests/Support/OpenApi/SpecAssertionHelper.php` parses the spec once per test run (cached statically), validates it structurally at load, and exposes `assertResponseMatches($response, $method, $path)` which locates the operation, confirms the response status is declared, and walks the declared body schema (envelope keys, nested required keys, arrays, enums, basic type checking) against the actual JSON. Convenience trait `tests/Support/OpenApi/AssertsOpenApiResponse.php` wraps it as `assertResponseMatchesOpenApiSpec($response, $method, $path)`.
- Conformance suite: `tests/Feature/OpenApiConformanceFeatureTest.php` (18 tests) covers the happy-path and the canonical error shapes (422 validation, 401 unauth, 404 not_found) for every in-scope endpoint; `tests/Unit/Support/OpenApi/SpecAssertionHelperTest.php` (4 tests) locks the parse + path coverage + `ApiErrorCode` parity. `tests/Unit/Architecture/OpenApiContractSourceGuardrailTest.php` (8 tests) locks spec existence, OpenAPI 3.0 declaration, structural validity, the 14-path coverage, the `ApiErrorCode` enum parity, the composer dev dependency, and the existence of helper + trait + conformance test.
- Roadmap wording updated from "OpenAPI 3.1" to "OpenAPI 3.0" in the Closed-block definition; the convergence-wave `C0-C7` API freeze references are now anchored against the spec.

Convergence impact: a machine-readable API freeze protects `/api/v1/*` compatibility through the physical module moves of `C1-C7`. The spec is the source of truth for the freeze; the conformance suite catches drift before it reaches the module moves.

DoD: covered routes fail tests on any response/spec divergence (enforced by `OpenApiConformanceFeatureTest`); spec structurally validated (enforced by `OpenApiContractSourceGuardrailTest`); envelope and error taxonomy formalized; no runtime contract changes (controllers, DTOs, middleware untouched).

Convergence impact: a machine-readable API freeze protects `/api/v1/*` compatibility through the physical module moves of `C1-C7`.

### C0 — Closed (`2026-07-05`) - Module Boundary Foundation

Priority: `P1`. Sequence: after `S1` (closed; the API freeze is the contract reference for module moves).

**Status: closed on `2026-07-05`.** Delivered:

- `docs/ARCHITECTURE.md` extended with a `## Module Boundary Contract` subsection under the existing `## Modular Monolith Target Layout` section. The contract formalizes:
  - **Module public API = `app/Domains/<Module>/Contracts/`** (interfaces + DTOs + value objects + enums; no Eloquent models at the boundary).
  - **Cross-module imports go through Contracts only**: within `app/Domains/<Module>/`, an import of `App\Domains\<OtherModule>\` is allowed only when the imported namespace is `<OtherModule>\Contracts\`.
  - **Always-allowed namespaces**: shared domain kernel (`App\Domain\*`), cross-cutting infrastructure (`App\Support\*`), and the legacy bridge namespaces (`App\Contracts\*`, `App\Application\*`, `App\Services\*`, `App\Repositories\*`, `App\Http\*`, `App\Models\*`, plus `App\Exceptions\*`/`App\Policies\*`/`App\Providers\*`/`Database\Factories\*`/`Database\Seeders\*`). The legacy bridge list only shrinks as modules relocate.
  - **Relocation mechanics**: namespace move per slice, no dual-namespace shims, no `class_alias`, atomic with route/DI/test updates; `/api/v1/*` envelope preserved (verified by the S1 conformance suite).
  - **Provider re-registration**: each module ships a `<Module>ServiceProvider` that binds its Contracts; C0 adds none yet (no module has runtime code).
- `tests/Unit/Architecture/ModuleBoundaryGuardrailTest.php` (5 tests) enforces: (1) the legacy-bridge allowlist is the documented enumerable set; (2) cross-module imports target `<OtherModule>\Contracts\` only (namespace-aware use-statement scanner, not literal substring); (3) module-internal controllers depend on Application handlers only (not Services/Repositories directly); (4) module-internal application handlers don't import HTTP transport types; (5) module-internal repositories stay persistence-only. Passes trivially today (empty `app/Domains/*`) and becomes load-bearing with `C1`.
- `docs/REPO_MAP.md` extended under `## Target layout` with a per-module ownership table (`Module | Public API (Contracts surface) | Owning wave | Migration state`) and a cross-reference to the architecture contract.
- `docs/DOMAIN_MAP.md` extended with the `## Module Boundary Contract` cross-reference and per-context migration-state markers (`[migration: pending C1]` through `[migration: pending C7]`) in every context H3 section; a new `### Payments` H3 added to surface the gateway contract migration target for `C6`.

Verified baseline findings (from the pre-C0 mapping pass) feed the contract-design surface for `C1-C7`:

- `app/Domains/*` is empty of runtime code today (7 README-only module skeletons pinned by `ModularMonolithSkeletonGuardrailTest`).
- `app/Domain/*` (singular) is the shared domain kernel: 4 typed exceptions, `StatusTransitionSource` enum, `OrderPaymentStatusResolver`, `Money` value object. Cross-module imports from here must remain allowed; the guardrail allowlists it.
- Cross-context coupling today is dominated by `Application/<X>Handler → App\Services\<Y>` (concrete class, not contract). Application→Application DTO coupling across contexts is already zero; the C0 contract convention locks this precedent in.
- `Cart → Catalog` coupling exists only at the Eloquent model level (no application/service cross-reference), giving `C1` and `C3` clean contract surfaces to design.
- The hardest module-pair contract surface is `Payment ↔ Webhook ↔ Orders` (`PaymentService` consumes `WebhookProcessingPipeline`; `PaymentWebhookTransitionApplier` consumes four service namespaces). The C0 contract convention accommodates this pattern before `C6`/`C7` can move.

Convergence impact: the boundary is load-bearing before any runtime code moves. Every later wave (`C1-C7`) extends the guardrail to cover its newly introduced contracts and shrinks the legacy-bridge allowlist as one slice migrates; the documented allowlist makes the migration progress visible.

DoD: `ModuleBoundaryGuardrailTest` active and green; `docs/ARCHITECTURE.md` declares the contract; `REPO_MAP.md`/`DOMAIN_MAP.md` carry per-module ownership and migration markers; no runtime code moves (controllers, services, repositories untouched).

## Promoted Review Blocks (`2026-06-27`)

### Review Block R1 — Closed (`2026-07-04`) - API Error Contract And Stale-Aggregate Failure Taxonomy

Priority: `P1`. Sequence: after `Backlog F3`, before broader Backlog G domain expansion.

**Status: closed on `2026-07-04`.** Delivered:

- Extracted renderer into `app/Support/Api/ApiExceptionRenderer.php` and registered through `bootstrap/app.php` `$exceptions->render(...)`.
- Introduced additive `App\Support\Api\ApiErrorCode` closed enum and `error.code` field alongside the preserved `error.type` literal.
- Replaced bare `DomainException` throws in `PaymentService::initiate` and `ShippingService::createShipment` with `App\Domain\Exceptions\OrderStaleAggregateException`, rendered as HTTP **409 Conflict** with `stale_aggregate` code; queue context propagates to the worker and fails the job.
- Consolidated architecturally-significant Path B inline controller error responses (2 lookup-404 sites, 3 missing-header-400 sites) to throw `Symfony\Component\HttpKernel\Exception\*HttpException` subclasses routed through the renderer.
- Extended guardrails: `ApiErrorCodeStabilityGuardrailTest`, `ApiExceptionRendererBoundaryTest`, and the `ApiControllerDomainExceptionBoundaryTest` direct-`ApiResponse::error()` ban with a documented auth+webhook allowlist.
- Tests added: `tests/Unit/Support/Api/ApiExceptionRendererTest.php`, `tests/Feature/ApiErrorContractTest.php`; existing status-only tests on consolidated sites now assert `error.type` and `error.code`.

Original scope, kept for history:

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

### Review Refinement R2 (closed) (`2026-07-04`) - Exact Promotion Arithmetic And Idempotency Retention

**Status: closed on `2026-07-04`.** Delivered:

- `Money::percentage()` accepts an exact-decimal string rate (up to four decimal places) and computes the discount through integer per-million arithmetic with `PHP_ROUND_HALF_UP`, removing the float-rate entry point from the domain boundary. A deprecated `percentageFloat()` alias preserves backward compatibility for callers without an exact source.
- `CheckoutDiscountResolver::calculateDiscountTotal()` is now statically typed (`PromotionType $type, string $promotionValue, Money $subtotal`); the `(float) $promotion->value` cast and the `PromotionType|string` union are removed. The promotion value flows from the Eloquent `decimal:2` cast as an exact string and never crosses a float boundary.
- PERCENT branch defends its own boundary: rates outside `[0, 100]` throw `CheckoutException::promotionTypeInvalid` so a domain call without the HTTP validator cannot produce a discount larger than the subtotal.
- Both idempotency retention windows are now independently configurable through `config/checkout.php` (`checkout.idempotency.pending_minutes`, `checkout.idempotency.completed_hours`) with bounded positive-integer resolution (pending 1..10080 minutes default 30; completed 1..720 hours default 24), matching the `AUTH_LOGIN_THROTTLE_*` pattern. The hardcoded `addMinutes(30)` (×2) and `addHours(24)` (×1) sites are replaced by config reads.
- All four environment examples (`.env.example`, `.env.stage.example`, `.env.prod.example`, `.env.testing`) declare `CHECKOUT_IDEMPOTENCY_PENDING_MINUTES=30` and `CHECKOUT_IDEMPOTENCY_COMPLETED_HOURS=24`.
- Extended guardrails: `CheckoutIdempotencyAndPromotionArithmeticGuardrailTest` forbids the float cast, the union signature, the hardcoded retention literals, and locks both config keys + documented defaults.
- Tests added: `tests/Unit/Domain/ValueObjects/PromotionValueRateTest.php` (string-rate arithmetic, cent-edge half-up, format validation), `tests/Unit/Checkout/CheckoutDiscountResolverTest.php` (percent/fixed/capped/defensive), `tests/Unit/Checkout/CheckoutIdempotencyRetentionConfigTest.php` (defaults, overrides, bounded-resolver isolated-process validation).

Original scope, kept for history:

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

### Review Block R3 (closed) (`2026-07-04`) - Alert Delivery Outcome Observability

**Status: closed on `2026-07-04`.** Delivered:

- Channel delivery contract moves from `bool` to an explicit `AlertDeliveryOutcome` enum (`disabled`, `delivered`, `failed`) so the router can distinguish intentional channel disablement from a failed delivery. The `ObservabilityAlertChannel::send()` return type, all three concrete channels (email/Slack/PagerDuty), and the in-test channel stub now resolve against the enum.
- `ObservabilityAlertRoutingResultDto` is enriched with three outcome buckets (`deliveredChannels`, `disabledChannels`, `failedChannels`) plus `hasAttemptedDeliveries()` and `everyAttemptedDeliveryFailed()` helpers. The historical `sentChannels` field is removed in favor of `deliveredChannels`; the console command consumer and feature assertion are updated to read the new property and to differentiate the all-disabled output from the all-attempted-failed output.
- The router emits the aggregate operational signal `observability.alert_routing_aggregate_failure` (owned by `ObservabilityAlertRoutingLogger::aggregateFailure()`) only when at least one enabled channel attempted delivery and every attempt failed. Disabled channels never trigger the aggregate warning, so a fully-disabled configuration does not create false delivery-failure alerts.
- Cooldown activation stays gated on at least one successful delivery; partial success and cooldown-suppressed behavior remain backward-compatible.
- Extended guardrail: `AlertDeliveryOutcomeContractGuardrailTest` requires the enum return type on the channel contract, forbids `bool` returns in concrete channels, and locks the router outcome-classification + aggregate-failure emission and the enriched DTO shape.
- Tests added: `tests/Unit/Support/Observability/AlertDeliveryOutcomeTest.php` (taxonomy and predicates), `tests/Unit/Support/Observability/Channels/{Email,Slack,PagerDuty}ObservabilityAlertChannelTest.php` (disabled/delivered/failed matrix per channel), and the rewritten `tests/Unit/Support/Observability/ObservabilityAlertRouterTest.php` (all-disabled, all-failed, partial-success, full-success, cooldown-activation, and cooldown-suppressed matrices).

Original scope, kept for history:

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

### Wave C0 — Closed (`2026-07-05`) - Module Boundary Foundation

1. Define the module contract convention: a module exposes `Contracts` (interfaces + DTOs) and application handlers as its public API; everything else is module-private.
2. Add a module-boundary guardrail: within `app/Domains/*`, cross-module imports are allowed only from another module's `Contracts` namespace; transport, application, service, and repository layer direction rules apply unchanged inside modules.
3. Decide and document relocation mechanics in `docs/ARCHITECTURE.md` (namespace move per slice, no dual-namespace compatibility shims, provider re-registration policy).
4. Update `docs/REPO_MAP.md` and `docs/DOMAIN_MAP.md` with per-module ownership and the migration state marker per module.

DoD: guardrail active before any runtime code moves; architecture/maps documents define the module contract; no runtime relocation in this wave. **Met on `2026-07-05`.**

Entry criteria: `R1` and `S1` closed (stable error taxonomy and a machine-readable spec precede the module API freeze). **Met before activation.**

### Wave C1 — Closed (`2026-07-05`) - Catalog Module

Priority: `P1`. Sequence: after `C0` (closed; the module-boundary guardrail and the relocation mechanics are in place).

**Status: closed on `2026-07-05`.** Delivered:

- Public catalog read slice physically relocated into `app/Domains/Catalog/*` with namespace moves and zero dual-namespace shims:
  - `Controllers/` — `CatalogController` (now `final`) + `CatalogIndexRequest`.
  - `Application/Queries/` — 3 query handlers (`PaginateCatalogProductsHandler`, `GetCatalogProductBySlugHandler`, `ListCatalogCategoriesHandler`) + 2 query payloads.
  - `Application/Dto/` — 7 result DTOs (module-internal).
  - `Services/` — `CatalogService` (now `implements CatalogReadService`) and `CatalogVersionService` (now `implements CatalogCacheVersion`).
  - `Repositories/` — `CatalogProductReadRepository` (implements the relocated contract).
  - `CatalogServiceProvider.php` — binds 3 contracts, registered in `bootstrap/providers.php` immediately after `ApplicationBindingsServiceProvider`.
- Module public API in `app/Domains/Catalog/Contracts/`:
  - `CatalogProductReadRepository` — paginated catalog reads; keeps `Product` at the boundary as a documented shared-kernel allowance pending the model-ownership wave.
  - `CatalogCacheVersion` — `current()`/`bump()` contract; consumed by 3 admin services (`AdminCatalogService`, `AdminCategoryService`, `AdminCacheService`) so admin writes can invalidate the read cache without importing module-private services.
  - `CatalogReadService` — `list`/`productBySlug`/`categories` contract; consumed by module query handlers and by the two performance-smoke scenarios measuring service-level query budgets.
  - `Dto/CatalogProductListFilterDto` — typed filter DTO consumed by `PerformanceSmokeSetupFactory` and `PerformanceSmokeContextDto`.
- Wiring updates atomic with the namespace move:
  - `routes/api.php` import switched to `App\Domains\Catalog\Controllers\CatalogController`.
  - `ApplicationBindingsServiceProvider` no longer binds the catalog repository; that binding moved to `CatalogServiceProvider`.
  - 3 admin services now depend on `CatalogCacheVersion` (contract), not on the concrete `CatalogVersionService`.
  - 2 performance-smoke scenarios now depend on `CatalogReadService` (contract), not on the concrete `CatalogService`.
  - 2 performance-smoke infrastructure files (`PerformanceSmokeSetupFactory`, `PerformanceSmokeContextDto`) import `CatalogProductListFilterDto` from the new `Contracts\Dto\` namespace.
  - `psalm.xml` per-file `TooManyTemplateParams` suppression relocated to `app/Domains/Catalog/Services/CatalogService.php`; `app/Domains/Catalog/Repositories` directory added to `TooManyTemplateParams` and `InvalidDocblock` suppressions (the repository carries the same `Closure(Relation<*, *, *>)` eager-load shapes that the legacy `app/Repositories` directory has).
- Tests:
  - `tests/Feature/CatalogModuleRelocationTest.php` (new, 2 tests) locks the contract bindings and the route namespace post-move.
  - `tests/Feature/SpaShellCacheTest.php` (new) — the unrelated SPA-shell cache test extracted out of `CatalogTest.php`.
  - `tests/Feature/CatalogTest.php` — kept; no PHP imports of catalog classes (HTTP-only).
  - `tests/Unit/CatalogVersionServiceTest.php` resolves via `app(CatalogCacheVersion::class)`.
  - `tests/Unit/ApplicationRepositoryBindingTest.php` catalog row updated to the new contract/implementation pair.
  - `tests/Unit/Architecture/RepositoryReadBoundaryTest.php` — catalog-product row removed (file moved); admin-product row forbidden-namespace strings updated to the new catalog module namespaces.
  - `tests/Unit/Architecture/RepositoryBusinessDecisionBoundaryTest.php` — catalog-product entries removed (file moved; cross-module boundary now enforced by `ModuleBoundaryGuardrailTest`).
- Frontend: untouched. `resources/js/api/catalog.ts`, composables, mappers, contracts all stay byte-identical; the wire contract is locked by `docs/api/openapi.yaml` (S1) and verified by `OpenApiConformanceFeatureTest`.

Invariants preserved: `/api/v1/catalog/*` paths, verbs, schemas, status codes, throttle (`search`), cache-control (`public, max-age=60`) byte-identical (verified by `OpenApiConformanceFeatureTest`); `catalog:version` cache key unchanged; admin write behavior unchanged (only the constructor type moved from concrete to contract); Eloquent models stay shared under the `App\Models\` legacy-bridge allowance; `ModuleBoundaryGuardrailTest` continues to pass with the new module content; performance-smoke query-budget gates (`≤8 queries` for catalog list) still green.

Convergence impact: the C0 boundary contract proved load-bearing on the first runtime move. The legacy-bridge allowlist effectively shrinks by one concrete service class (`CatalogService` is no longer in `App\Services\*`; admin and smoke consumers now reach it through `App\Domains\Catalog\Contracts\CatalogReadService`). The two new contracts (`CatalogCacheVersion`, `CatalogReadService`) document the cross-module surface and become the precedent for `C2-C7`.

DoD: catalog routes serve identical contracts from the module namespace; guardrails updated; old namespaces removed in the same block. **Met on `2026-07-05`.**


### Wave C2 — Closed (`2026-07-05`) — Users/Auth Module

Move auth transport (`Api/V1/Auth/*`), `Application/Auth/*` (issuer, revalidator, audit logger, handlers, contracts), auth repositories, account orders read slice, and `EnsureActiveApiUser` wiring into `app/Domains/Users`. Module name `Users` covers the Auth + Account bounded context per `docs/DOMAIN_MAP.md`.

DoD: auth/account routes serve identical contracts from the module namespace; guardrails updated; old namespaces removed in the same block; `UsersServiceProvider` registered in `bootstrap/providers.php`; `active.api.user` middleware alias resolves to the module namespace. **Met on `2026-07-05`.**

Closed scope (`2026-07-05`):
- 4 controllers + 6 FormRequests relocated to `app/Domains/Users/Controllers/`; 3 controllers + 4 FormRequests made `final` on move.
- Application layer relocated (`Commands/`, `Queries/`, `Dto/`) covering 8 Auth command handlers, 1 Auth query handler, 4 Account Orders query handlers, 7 Auth DTOs, 11 Account DTOs, 3 top-level Application classes (`AuthAccessTokenIssuer`, `AuthActiveUserRevalidator`, `AuthApplicationException`).
- 4 contracts relocated to `app/Domains/Users/Contracts/` (`AuthUserRepository`, `AuthPasswordBrokerRepository`, `AuthAuditLogger`, `AccountOrderReadRepository`); 5 Support classes + 1 Account Orders projector relocated.
- 3 repositories relocated to `app/Domains/Users/Repositories/`.
- `EnsureActiveApiUser` middleware relocated to `app/Domains/Users/Middleware/`; alias `active.api.user` registered to the new FQCN.
- `ObservabilityAuthAuditLogger` relocated to `app/Domains/Users/Infrastructure/`.
- `AuthBindingsServiceProvider` renamed to `UsersServiceProvider` and moved into the module; absorbed the `AccountOrderReadRepository` binding previously owned by `ApplicationBindingsServiceProvider`.
- `UsersServiceProvider` registered in `bootstrap/providers.php`; `ApplicationBindingsServiceProvider` shrunk.
- Routes (`/api/v1/auth/*` and `/api/v1/account/orders/*`) updated to the new controller namespace; route names `verification.verify`/`verification.send` preserved.
- Operational contracts preserved: `AuthAuditEvent` literal values, `DUMMY_PASSWORD_HASH` timing-safety, sanctum token expiration, login throttle, password policy.
- Guardrails updated: `ApplicationAuthRepositoryBoundaryTest`, `AuthAuditEmissionGuardrailTest` (now also scans `app/Domains/Users/Repositories`), `AuthCredentialHardeningGuardrailTest`, `AuthTokenLifecycleGuardrailTest`, `SecurityConfigGuardrailTest` (literal FQCN), `InfrastructureProviderBoundaryTest` (provider list), `RepositoryReadBoundaryTest` (path), `RepositoryBusinessDecisionBoundaryTest` (path + import), `ModuleBoundaryGuardrailTest` (passes trivially — Users has no outbound `Domains\*` imports).
- 9 unit tests relocated to `tests/Unit/Application/Users/`; 6 feature tests reference the new namespaces; `UsersModuleRelocationTest` added (smoke for contract bindings, controller namespace, middleware alias, route names).
- `psalm.xml` suppressions extended for the module repositories and DTO directories.
- `app/Domains/Users/README.md` replaced with the active module documentation (subfolders, contract surface, operational contracts, migration state).
- No new Contracts files needed: the 4 relocated contracts are module-internal (no other module consumes them today). Strict subset of C1's contract surface.
- Shared `ResolvesAuthenticatedUser` trait and `AppliesOrderSearch` trait stay in legacy bridge per C0 (consumed cross-module by Cart/Checkout).


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
6. Static-analysis asymmetry - `confirmed; promoted as Q3; closed 2026-07-04`:
   - PHPStan level `10` over `app/routes/tests` vs Psalm level `6` over `app/database` only. Closed by Q3: Psalm now runs level 4 over `app/routes/database` with `psalm/plugin-laravel` for Eloquent type inference. Plugin-version follow-up tracked at #22 (upgrade path to plugin v3.14 requires PHP ~8.4.3 + Psalm ^6.16.1, blocked by the current OSPanel-managed PHP 8.4.1 runtime).
22. `psalm/plugin-laravel` upgrade follow-up - `opened 2026-07-04`:
   - The Q3 Psalm ladder landed on plugin v3.0.x (the only line compatible with the OSPanel-managed PHP 8.4.1 runtime and pinned Psalm 6.4.1). The 2-parameter Laravel-12 template-arity convention (`<TKey, TValue>` for paginator; `<TRelatedModel, TDeclaringModel>` for relations) is suppressed via `psalm.xml` `issueHandlers` with enumerable scope; upstream fix requires plugin v3.14 (PR #1082) and v3.14.2 (PR #1141), both gated by PHP ~8.4.3 + Psalm ^6.16.1. Once the runtime is upgraded, the suppressions are removable in a single edit and the next-level Psalm ladder (level 3 and below) becomes actionable.
7. Frontend hardening headroom - `confirmed; promoted as Q4; closed 2026-07-04`:
   - `strict: true` without `noImplicitOverride`/`noFallthroughCasesInSwitch`/`noUncheckedIndexedAccess`; no vitest coverage signal. Closed by Q4: `noImplicitOverride` + `noFallthroughCasesInSwitch` enabled with zero churn; v8 coverage reporting wired with baseline observed (86.96% statements / 75.35% branches). `noUncheckedIndexedAccess` deferred to follow-up (risk register #23) after measuring 55 errors (2 production, 53 test-only) and deciding that test-fixture tightening should land first.
23. `noUncheckedIndexedAccess` follow-up - `opened 2026-07-04`:
   - The Q4 frontend hardening block measured 55 errors with the flag enabled: 2 production (`AdminProductVariantsSection.vue:17`, `useAdminOrderDetailsState.ts:44`) + 53 test-only (concentrated in `use-admin-mutation-flows.spec.ts` 21, `admin-component-contracts.spec.ts` 11, plus five other test files). The flag was deferred to avoid widespread `!`/`as` workarounds in test fixtures. The follow-up tightens test fixtures to typed factories (eliminating the indexed-access widening at the source), fixes the 2 production cases with proper guards, and flips `FrontendTypeAndTestSignalGuardrailTest::test_tsconfig_does_not_enable_no_unchecked_indexed_access_yet` to assert presence.
8. No machine-readable API contract - `confirmed; promoted as S1; closed 2026-07-04`:
   - `/api/v1` shape is maintained twice by hand (PHP DTO mapping, TS assertions) with no schema artifact. Closed by S1: `docs/api/openapi.yaml` (OpenAPI 3.0.3) is the source of truth for 14 in-scope paths (auth + catalog + cart); `devizzent/cebe-php-openapi ^1.1.5` parses/validates the spec; `SpecAssertionHelper` + `AssertsOpenApiResponse` trait enforce response conformance in `OpenApiConformanceFeatureTest` (18 tests); guardrail locks spec existence, structural validity, `ApiErrorCode` parity, and toolchain presence. OpenAPI 3.1 upgrade tracked at #24 (no stable PHP validator with Symfony YAML v8 support exists).
24. OpenAPI 3.1 upgrade follow-up - `opened 2026-07-04`:
   - The S1 spec is authored against OpenAPI 3.0.3, not 3.1 as originally planned. The original roadmap wording referenced 3.1; the closure downgraded to 3.0 because `cebe/php-openapi` stable supports 3.0.x only, and the `devizzent` fork's 3.1 support is exercised for spec loading but the 3.0 dialect is sufficient for this API (no webhooks, no JSON Schema dialect override, nullable modeled via `nullable: true`). The upgrade becomes meaningful when the API needs 3.1 features (webhooks, exclusive-bounds, examples array) or when a stable PHP validator with Symfony YAML v8 + 3.1 support arrives; the `devizzent` fork already supports 3.1, so the spec dialect can be bumped in a single PR when justified.
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

Completed (see registry and execution log): typed webhook payload boundaries; `*FilterDto` migration; enum-based admin order status input; DTO result boundaries for all handlers; async shipping ingestion parity; additive canonical account routes; admin selector endpoints; cleanup config/indexes; `CatalogIndexRequest`; `ResolvesAuthenticatedUser`; canonical `formatPrice`; bounded read repositories + summary projector; finite Sanctum token expiration with active-user revalidation and split revoke semantics; shared password policy, identity-aware login limiter, and timing-parity credential verification; structured auth security audit trail (`AuthAuditLogger` contract, stable `auth.audit.*` event taxonomy, whitelisted context with `sha256` email hash on failure paths); strict Eloquent runtime mode in non-production environments, `CarbonImmutable` global resolver, and immutable model date casts across all models; CI supply-chain audit gate (`composer audit` + `npm audit --omit=dev --audit-level=high`) with dependabot-driven weekly update PRs and a dated advisory exception policy.

Pending, owned by queued blocks:

1. ~~`R1`: additive stable `error.code` through a dedicated renderer; Orders-owned stale-aggregate failure with context-specific handling; `error.type` preserved until an approved deprecation migration.~~ — closed `2026-07-04`.
2. ~~`R2`: exact decimal/rate promotion boundary; separate validated pending/completed idempotency retention config.~~ — closed `2026-07-04`.
3. ~~`R3`: typed alert-channel delivery outcomes (`disabled`/`delivered`/`failed`).~~ — closed `2026-07-04`.
4. ~~`A2`: queued job payloads gain a scalar `correlation_id` key restored into log context.~~ — closed.
5. ~~`A1`: new `app:orders-reconcile` command with validated `reconciliation.*` config windows and scheduler registration.~~ — closed `2026-07-04`.
6. ~~`80/81`: privilege/state fields removed from `$fillable`; `config/cors.php`, `config/security.php`, `ForceHttpsMiddleware`, `session.secure` non-null default; five new env keys documented across environments.~~ — closed `2026-07-04`.
7. ~~`82/83`: closed-shape address and gateway payload boundaries enforced by guardrails; central data-classification inventory under `docs/SECURITY_DATA_CLASSIFICATION.md`; unified `SecurityConfigGuardrailTest` aggregates the cross-cutting security-config contract.~~ — closed `2026-07-04`.
8. `S1`: `docs/api/openapi.yaml` becomes the machine-readable `/api/v1` contract, validated in CI and feature tests.
9. `C0-C7`: module public-API convention under `app/Domains/*` with cross-module imports restricted to `Contracts` namespaces.
10. Field-level encryption follow-up (candidate): encrypted casts on `orders.billing_address`/`shipping_address` and provider `payload` columns; requires `APP_ENCRYPTION_KEY` lifecycle plan, backfill migration, query-shape impact analysis, and rollback path. Prerequisites (closed JSON shapes, central inventory, `JsonPayload` abstraction at every site) satisfied by `82/83`.

## Risk Register

| Risk | Owner block | Mitigation |
| --- | --- | --- |
| Audit trail leaks PII/secrets into logs | F3-79 | Context-key whitelist with a dedicated leak test; email hashed on failure paths |
| New `error.code` taxonomy churns into a second unstable contract | R1 | Codes are literal constants from one taxonomy; additive-only; feature matrix locks values |
| Exact-decimal migration changes computed totals | R2 | Half-up rounding fixed by tests on cent edges; existing fixtures asserted byte-compatible (closed `2026-07-04`) |
| Idempotency window misconfiguration breaks replay semantics | R2 | Bounded positive-int validation; override tests prove replay/mismatch behavior per window (closed `2026-07-04`) |
| Module relocation conflicts with parallel feature work | C1-C7 | One module per block; atomic move with tests; no dual namespaces; entry criteria gate the start. C0 boundary contract (`2026-07-05`) makes the contract surface explicit before any slice moves. |
| Guardrail erosion during moves (allowlist growth) | C0-C7 | `AGENTS.md` shrink-only allowlist rule; module-boundary guardrail lands before first move (C0, closed `2026-07-05`). `LEGACY_BRIDGE_NAMESPACES` is enumerable and asserted against the documented allowlist; the list only shrinks as modules relocate. |
| Cross-context service coupling (Application handler → concrete Service) blocks clean module moves | C1-C7 | Pre-C0 mapping pass recorded the coupling surface (`Application/<X>Handler → App\Services\<Y>` is the dominant pattern; `Payment ↔ Webhook ↔ Orders` is the hardest pair). Each wave defines a contract at the boundary before the slice moves; legacy `App\Contracts\*` stays allowlisted until retired. |
| Reconciliation false positives page on-call | A1 | Config-driven detection windows; alerts flow through the existing cooldown router |
| Mass-assignment regression reintroduces privilege/state into `$fillable` | 80/81 | `SensitiveStateFillableGuardrailTest` + `SensitiveFieldsRejectMassAssignmentTest` lock the contract; allowlist-only-shrinks rule applies |
| Transport-security drift breaks cookies/CORS/HTTPS in deployment | 80/81 | `TransportSecurityBaselineGuardrailTest` enforces file invariants; env defaults ship secure-cookie `true`, force-https `true` in non-local; local-env exemption prevents dev breakage |
| PII smuggled into persisted address/gateway JSON columns widens leak blast radius | 82/83 | `AddressPayloadBoundaryGuardrailTest` and `GatewayPayloadBoundaryGuardrailTest` lock the closed-shape contracts; `SecurityDataClassificationDocGuardrailTest` keeps the inventory honest; field-level encryption follow-up stays a candidate with explicit prerequisites |
| Future real-provider payment/shipping adapter leaks cardholder data into the payload column | 82/83 | `GatewayPayloadBoundaryGuardrailTest` forbids PII literals and requires `JsonPayload` construction; the boundary is in place before any real adapter lands |
| Security-config drift ( Sanctum TTL, login throttle, secure cookie, force-https, CORS scope, active-user revalidation ) goes unnoticed | 82/83 | `SecurityConfigGuardrailTest` aggregates the cross-cutting contract into one canary; point guardrails remain the per-contract authority and continue to assert their narrow invariants |
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
14. CI blocks known high/critical dependency advisories; automated update PRs active — `SupplyChainAuditGateGuardrailTest`, `.github/workflows/ci.yml`, `.github/dependabot.yml` (`Q2`).

Remaining (each verified by its owning block's DoD):

15. Stable additive `error.code` through a dedicated renderer; typed stale-aggregate failures across HTTP/orchestration/queue call sites — `R1`.
16. Exact promotion arithmetic to the JSON boundary; independently configurable idempotency windows — `R2` (closed).
17. Alert routing distinguishes disabled channels from attempted-delivery failures with aggregate all-failed signal — `R3` (closed).
18. One correlation id joins HTTP ingress, queued processing, and side-effect logs — `A2` (closed).
19. ~~Every silent side-effect-loss window has a bounded, alerting detection time; `failed_jobs` is monitored — `A1`.~~ — closed `2026-07-04`.
20. ~~Privilege/state fields structurally excluded from mass assignment; transport security baseline versioned (CORS allowlist, secure-cookie default, proxy-aware HTTPS enforcement) — `80/81`.~~ — closed `2026-07-04`.
21. ~~Closed-shape address and gateway payload boundaries enforced by architecture guardrails; central PII data-classification inventory documented; cross-cutting security-config contract aggregated into a single rollup guardrail — `82/83`.~~ — closed `2026-07-04`.
22. ~~Psalm level `4` or stricter clean on extended scope — `Q3`.~~ — closed `2026-07-04`. Psalm runs at `errorLevel="4"` over `app/routes/database` with `psalm/plugin-laravel` registered; `TooManyTemplateParams` and `InvalidDocblock` plugin-version suppressions enumerable and documented (see risk register #22 for upgrade path).
23. ~~Frontend hardening flags enabled; per-run coverage signal visible in CI — `Q4`.~~ — closed `2026-07-04`. `noImplicitOverride` + `noFallthroughCasesInSwitch` enabled in `tsconfig.json` with zero churn; `@vitest/coverage-v8` provider wired in `vitest.config.ts` with text+html reporters; `npm run test:coverage` produces baseline report (86.96% statements / 75.35% branches). `noUncheckedIndexedAccess` deferred (see risk register #23 for follow-up prerequisites).
24. ~~Covered `/api/v1` routes are validated against a machine-readable spec in CI — `S1`.~~ — closed `2026-07-04`. `docs/api/openapi.yaml` (OpenAPI 3.0.3) covers 14 in-scope paths (auth + catalog + cart); `OpenApiConformanceFeatureTest` (18 tests) enforces response conformance via `SpecAssertionHelper`; `OpenApiContractSourceGuardrailTest` (8 tests) locks spec existence, structural validity, `ApiErrorCode` parity, and toolchain presence. OpenAPI 3.1 dialect deferred (risk register #24).
25. ~~Module-boundary guardrail active before any runtime code moves — `C0`.~~ — closed `2026-07-05`. `ModuleBoundaryGuardrailTest` (5 tests) enforces cross-module Contracts-only imports, enumerable legacy-bridge allowlist, and module-internal layer direction; `docs/ARCHITECTURE.md` declares the contract; `REPO_MAP.md`/`DOMAIN_MAP.md` carry per-module ownership and migration markers. First runtime slice (`C1` Catalog) still pending promotion.
26. ~~First module slices (Catalog, Users) serving production traffic from `app/Domains/*` — `C1-C2`.~~ — full close `2026-07-05`. `C1` (Catalog) achieved: public read slice lives in `app/Domains/Catalog/*` with contract surface `CatalogProductReadRepository` + `CatalogCacheVersion` + `CatalogReadService` + `Dto/CatalogProductListFilterDto`; admin writes consume `CatalogCacheVersion`; performance-smoke scenarios consume `CatalogReadService`; `CatalogServiceProvider` registered in `bootstrap/providers.php`. `C2` (Users) achieved: Auth + Account bounded context lives in `app/Domains/Users/*` (4 controllers + 6 FormRequests + 8 Auth command handlers + 5 query handlers + 4 contracts + 3 repositories + middleware + infrastructure); `UsersServiceProvider` registered in `bootstrap/providers.php`; `active.api.user` alias and route names `verification.verify`/`verification.send` preserved; 4 module contracts are module-internal (no other module consumes them today); verified by `UsersModuleRelocationTest`.

27. Remaining module slices (Cart, Checkout, Orders, Payments, Webhooks) serving production traffic from `app/Domains/*` — `C3-C7`.

## Backlog Intake Rule

1. `docs/DEEP_ARCHITECTURE_AUDIT_2026_03.md` and `docs/DEEP_ARCHITECTURE_AUDIT_2026_03_V2.md` are aligned backlog inputs, not active execution authority.
2. Audit findings remain candidate backlog until explicitly promoted into this file as waves or blocks.
3. Promotion preserves the architecture-first sequence: safety and locking; backend boundary quick wins; frontend consistency; deep domain expansion; platform enablement.
4. Deep domain items (`Money` completion, `app/Domain` expansion, checkout orchestrator growth, domain-event rollout) require separate approval and must not be bundled into quick-win slices.
5. Security promotion order inside the v2 intake is fixed: token/session lifecycle (`77`, closed); credential hardening (`78`, closed); auth audit trail (`79`, closed); mass-assignment surface + transport security baseline (`80`, `81`); data-at-rest minimization + security guardrails (`82`, `83`).
6. The `2026-06-27` external review is promoted only through `R1`/`R2`/`R3`; findings already covered by Backlog G/I2 are scope refinements, not duplicate items.
7. The `2026-07-03` internal code review is promoted only through `Q1-Q4`, `A1`/`A2`, and `S1`; its remaining findings (provider enablement, browser E2E, coverage floor) stay candidates until explicitly promoted, and provider enablement additionally requires a business decision.
8. A size/complexity hypothesis creates no work item without a concrete boundary violation, duplicated behavior, race, or untestable side effect.
9. Every promoted block must declare its modular-monolith convergence impact.

## Mandatory Test Matrix

1. Architecture guardrails (enforced now):
   - full API V1 controller boundary coverage; no ORM/paginator returns from handlers; no inline `$request->validate()`; repository business-decision and status-interpretation bans; jobs/listeners afterCommit discipline; policy completeness matrix; token lifecycle and credential-hardening contracts; no repository-level audit logging (`F3-79`); strict-mode and immutable-date wiring (`Q1`); supply-chain audit gate (CI audit steps + dependabot + README exception policy) (`Q2`); documentation authority and map governance.
   - added by queued blocks: correlation payload key on queued jobs (`A2`); dedicated renderer ownership with literal error-code taxonomy (`R1`); reconciliation scheduler wiring (`A1`); module cross-import restriction to `Contracts` (`C0`); sensitive-state fillable exclusion (`SensitiveStateFillableGuardrailTest` + `SensitiveFieldsRejectMassAssignmentTest`) and transport-security baseline invariants (`TransportSecurityBaselineGuardrailTest`) (`80/81`); closed-shape address and gateway payload boundaries (`AddressPayloadBoundaryGuardrailTest` + `GatewayPayloadBoundaryGuardrailTest`), security-classification inventory governance (`SecurityDataClassificationDocGuardrailTest`), and cross-cutting security-config rollup (`SecurityConfigGuardrailTest`) (`82/83`).
2. Feature tests:
   - webhook parity and idempotency; admin status transition validation; account order contract parity; payload hash mismatch and signature failures; finite/expired token behavior, inactive-user revalidation, current-token logout, password-reset global revoke; weak-password matrix, email+IP lockout, and known/unknown-email envelope parity.
   - added by queued blocks: audit-record presence per auth flow (`F3-79`); correlation propagation through queued flows (`A2`); `error.code` + legacy `error.type` compatibility matrix, stale-order transport behavior (`R1`); config-driven retention override semantics (`R2`); reconciliation detection matrices (`A1`); HTTPS enforcement redirect/loop-guard/local-exempt behavior (`80/81`); spec-validation of covered routes (`S1`).
3. Unit tests:
   - transition policies; checkout/cart collaborators; observability modules; cleanup strategy; summary projection; shared format utility; password-policy composition, limiter-key derivation, and dummy-hash verification.
   - added by queued blocks: audit context whitelist (`F3-79`); renderer status/code/type matrix and typed stale failures (`R1`); exact-rate rounding and retention config (`R2`); channel outcome and aggregate-failure matrices (`R3`); mass-assignment rejection on privilege/state fields (`80/81`).
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
| `2026-07-04` | `Q2` closed: blocking `composer audit` and `npm audit --omit=dev --audit-level=high` steps added to the CI quality gate; `.github/dependabot.yml` schedules weekly update PRs for composer/npm/github-actions; README documents the audit gate and the dated advisory exception policy (no audit allowlist); `SupplyChainAuditGateGuardrailTest` enforces the contract; `A2` is active next |
| `2026-07-04` | `A2` closed: `CorrelationContext` accessor (singleton bound in `ObservabilityServiceProvider`) resolves the inbound `X-Correlation-Id` or generates a stable UUID in non-HTTP contexts; all five queued jobs (`DispatchShipmentJob`, `SendOrderConfirmationJob`, `SendOrderStatusChangedNotificationJob`, `ProcessPaymentWebhookJob`, `ProcessShippingWebhookJob`) capture a scalar `correlationId` in their payload and restore it into `Log::withContext()` at the start of `handle()`; side-effect listeners and webhook enqueue handlers resolve the correlation id via `CorrelationContext::currentOrNew()`; `WebhookProcessingPipeline` forwards the true ingress correlation into the `webhook.processing_failed` log context (event-id fallback retained only for direct pipeline calls without an inbound correlation); `QueuedJobSafetyGuardrailTest` extended with correlation-payload and `Log::withContext()` assertions; `CorrelationContextTest` (unit) and `WebhookCorrelationPropagationTest` (feature) added; scalar-only payload discipline preserved; `R1` is active next |
| `2026-07-04` | `R2` closed: `Money::percentage()` accepts an exact-decimal string rate (up to four decimal places) with integer per-million arithmetic and `PHP_ROUND_HALF_UP`; deprecated `percentageFloat()` alias preserves backward compatibility; `CheckoutDiscountResolver::calculateDiscountTotal()` is now statically typed (`PromotionType`, `string`, `Money`) and the `(float) $promotion->value` cast and `PromotionType\|string` union are removed; PERCENT branch defends its own `[0, 100]` boundary with `CheckoutException::promotionTypeInvalid`; both idempotency retention windows are independently configurable through `config/checkout.php` (`checkout.idempotency.pending_minutes` default 30 / max 10080, `checkout.idempotency.completed_hours` default 24 / max 720) with bounded positive-integer resolution matching `AUTH_LOGIN_THROTTLE_*`; the hardcoded `addMinutes(30)` (×2) and `addHours(24)` (×1) sites are replaced by config reads; four environment examples declare both `CHECKOUT_IDEMPOTENCY_*` keys; `CheckoutIdempotencyAndPromotionArithmeticGuardrailTest` forbids the float cast, the union signature, and the hardcoded literals, and locks both config keys + documented defaults; `PromotionValueRateTest`, `CheckoutDiscountResolverTest`, and `CheckoutIdempotencyRetentionConfigTest` cover string-rate arithmetic, percent/fixed/capped/defensive discount behavior, and bounded-resolver semantics; `R3` is active next |
| `2026-07-04` | `R3` closed: alert channel delivery contract moves from `bool` to an explicit `AlertDeliveryOutcome` enum (`disabled`/`delivered`/`failed`); the `ObservabilityAlertChannel::send()` return type, all three concrete channels (email/Slack/PagerDuty), and the in-test channel stub resolve against the enum; `ObservabilityAlertRoutingResultDto` is enriched with `deliveredChannels`/`disabledChannels`/`failedChannels` plus `hasAttemptedDeliveries()` and `everyAttemptedDeliveryFailed()` helpers; the historical `sentChannels` field is removed in favor of `deliveredChannels` and the console command consumer and feature assertion are updated; the router emits the aggregate operational signal `observability.alert_routing_aggregate_failure` (owned by `ObservabilityAlertRoutingLogger::aggregateFailure()`) only when at least one enabled channel attempted delivery and every attempt failed, so disabled channels never trigger false delivery-failure alerts; cooldown activation stays gated on at least one successful delivery; `AlertDeliveryOutcomeContractGuardrailTest` requires the enum return type on the channel contract, forbids `bool` returns in concrete channels, and locks the router outcome-classification + aggregate-failure emission and the enriched DTO shape; `AlertDeliveryOutcomeTest`, `tests/Unit/Support/Observability/Channels/{Email,Slack,PagerDuty}ObservabilityAlertChannelTest`, and the rewritten `ObservabilityAlertRouterTest` cover the disabled/delivered/failed matrix per channel and the all-disabled/all-failed/partial-success/full-success/cooldown matrices on the router; `A1` is active next |
| `2026-07-04` | `80/81` closed: privilege/state fields removed from `$fillable` on `User` (`is_active`), `Order` (`status`/`payment_status`/`shipment_status`), `Payment` (`status`), `Shipment` (`status`); legitimate transition paths migrated to explicit `forceFill([...])->save()` (`AdminOrderService::updateStatus`, `PaymentWebhookTransitionApplier`, `ShippingWebhookTransitionApplier`, `CheckoutOrderWriter`, `PaymentService`, `ShippingService`); factories continue to work through Laravel's internal `Model::unguarded()`, direct `$user->update(['is_active' => ...])` in auth tests migrated to `forceFill+save`; `config/cors.php` (env-driven allowlist via `CORS_ALLOWED_ORIGINS`, scoped to `api/*`, credentials disabled) and `config/security.php` (`APP_FORCE_HTTPS` default `true`, `APP_TRUSTED_PROXIES` default `*`, `APP_TRUSTED_HOSTS` default null) added; `app/Http/Middleware/ForceHttpsMiddleware.php` redirects HTTP→HTTPS (301) when `APP_ENV != local` and force-https enabled, with `X-Forwarded-Proto` honoring to prevent proxy redirect loops, registered globally in `bootstrap/app.php` after `ApiRequestTelemetryMiddleware`; `config/session.php` secure-cookie default changed from bare `env('SESSION_SECURE_COOKIE')` to `env('SESSION_SECURE_COOKIE', env('APP_ENV', 'production') !== 'local')`; five env keys documented across `.env.example`, `.env.stage.example`, `.env.prod.example`, `.env.testing` with prod/stage CORS allowlists pre-populated; `SensitiveStateFillableGuardrailTest`, `SensitiveFieldsRejectMassAssignmentTest`, `TransportSecurityBaselineGuardrailTest`, and `HttpsEnforcementTest` enforce the invariants; `82/83` is active next |
| `2026-07-04` | `82/83` closed: address payload boundary locked by `AddressPayloadBoundaryGuardrailTest` (DTO emits exactly `{line1, city, country, postcode}`; six address-blob construction sites under `app/` scanned for PII-key drift); gateway payload boundary locked by `GatewayPayloadBoundaryGuardrailTest` (`FakePaymentGateway` and `FakeShippingGateway` ban PII literals — `card`/`card_number`/`pan`/`cvv`/`cvc`/`ssn`/`password`/`recipient_name` — and require `JsonPayload::fromArray()` construction); central `docs/SECURITY_DATA_CLASSIFICATION.md` records PII-bearing columns (`users.email/phone/password`, `orders.email/billing_address/shipping_address`, `payments.payload`, `shipments.payload`), allowed key sets, plaintext-at-rest threat model, and field-level-encryption follow-up prerequisites, enforced by `SecurityDataClassificationDocGuardrailTest`; unified `SecurityConfigGuardrailTest` aggregates 12 cross-cutting invariants (finite Sanctum TTL, bounded login throttle, `session.secure` bool resolution + non-local default `true`, `security.force_https` bool, non-empty `security.trusted_proxies`, CORS list shape with `supports_credentials=false` and `api/*`-scoped paths, login route `throttle:auth.login`, `active.api.user` middleware alias, `auth:sanctum` → `active.api.user` route coverage); field-level encryption stays a roadmap candidate with explicit prerequisites; `Q3` is active next |
| `2026-07-04` | `Q3` closed: `psalm/plugin-laravel` (v3.0.x, the only line compatible with OSPanel PHP 8.4.1 + pinned Psalm 6.4.1) registered via `Psalm\LaravelPlugin\Plugin`; Psalm scope extended to `routes/` for PHPStan parity (`app/routes/database/factories/seeders`); `errorLevel` raised `6→5→4`; level 5 fix `AppServiceProvider::boot` migrated `$this->app->isProduction()` → `$this->app->environment('production')` (interface-declared contract vs concrete-only method); level 4 source-typing fixes — `RedundantCast` removed from `WebhookProcessingPipeline`/`MaintenanceCleanupExecutor`/`MaintenanceCleanupRetentionResolver`/`OrdersReconcileRunner`, `now()->timestamp` → `now()->getTimestamp()` in `ObservabilityAlertCooldownStore`, repository `@return` shapes tightened in `AdminProductReadRepository`/`CatalogProductReadRepository`, `Promotion` model annotated with full `@property` inventory (id/name/code/usage_limit/usage_count/starts_at/ends_at/created_at/updated_at), `ApiContractSmokeContextFactory` narrowed `firstOrCreate` with `assert($user instanceof User)`; documented plugin-version tradeoff — `TooManyTemplateParams` (4 directories/files) and `InvalidDocblock` (`app/Models`) suppressed via `psalm.xml` `issueHandlers` with enumerable scope, removable in a single edit once plugin v3.14 + PHP 8.4.3 + Psalm 6.16.1 are available (risk register #22); `PsalmLadderScopeParityGuardrailTest` (8 assertions) locks errorLevel ≤ 4, extended scope, plugin registration, baseline-free progression, `findUnusedBaselineEntry=true`, documented template-arity suppressions, composer constraint window, and `environment()` contract; `Q4` is active next |
| `2026-07-04` | `Q4` closed: `tsconfig.json` extended with `noImplicitOverride` + `noFallthroughCasesInSwitch` (both zero churn — existing Vue 3 + TypeScript code already followed the discipline); `noUncheckedIndexedAccess` measured at 55 errors (2 production: `AdminProductVariantsSection.vue:17`, `useAdminOrderDetailsState.ts:44`; 53 test-only concentrated in `use-admin-mutation-flows.spec.ts` 21, `admin-component-contracts.spec.ts` 11, plus five other test files) and **deferred** per Q4 DoD — test-fixture tightening to typed factories should land first (risk register #23); v8 coverage reporting wired — `@vitest/coverage-v8 ^4.1.9` (aligned with vitest `^4.0.18`), `vitest.config.ts` extended with `{ provider: "v8", reporter: ["text","html"], reportsDirectory: "coverage/", all: true }`, `package.json` `test:coverage` script added, `.gitignore` excludes `/coverage`; default `test` script stays `vitest run` (no implicit coverage in the gate); baseline coverage observed: 86.96% statements / 75.35% branches / 86.36% functions / 88.18% lines (2609 statements across `resources/js`); no coverage floor introduced (separate decision after baseline observation); `FrontendTypeAndTestSignalGuardrailTest` (5 tests, 17 assertions) locks the strict flags, the deferred state of `noUncheckedIndexedAccess`, the v8 provider config, the test:coverage script + dep, and the gitignore entry; `S1` is active next |
| `2026-07-04` | `S1` closed: `docs/api/openapi.yaml` (OpenAPI **3.0.3** — downgraded from roadmap's "3.1" because `cebe/php-openapi` stable is 3.0-only and no stable PHP validator supports 3.1 on the Symfony YAML v8 stack Laravel 12 ships; spec dialect bump is a single-PR follow-up, risk register #24) authored as the machine-readable source of truth for 14 in-scope paths (auth 8 + catalog 3 + cart 3); three top-level envelopes (`{data}`, `{data,meta}`, `{{error}}`) formalized; closed 9-member `ApiErrorCode` enum embedded as `components/schemas/ApiErrorCode`; two distinct error shapes modeled — `ErrorResponseController` (Shape A, controller-caught `AuthApplicationException`: `{message, request_id?, type}`, no `code`/`validation`) and `ErrorResponseRenderer` (Shape B, `ApiExceptionRenderer`-emitted: `{message, request_id?, type, code, validation?}` with `validation` on 422 only); component schemas (`AuthUser`, `AuthToken`, `CatalogProduct`, `CatalogProductVariant`, `CatalogCategory`, `Cart`, `CartItem`, `CartSummary`, `PaginationMeta`) built verbatim from `*ResultDto::toArray()` outputs (no JsonResource classes for these domains per ADR-0002); tooling — `devizzent/cebe-php-openapi ^1.1.5` added to `require-dev` (the actively maintained fork supporting `symfony/yaml ^3-8` required by Laravel 12; original `cebe/php-openapi 1.8.0` capped at `^7` is uninstallable; fork declares `replace: cebe/php-openapi` so no conflicts); `tests/Support/OpenApi/SpecAssertionHelper.php` parses spec once per run (cached statically, structurally validated at load) and walks declared body schemas against actual JSON; `AssertsOpenApiResponse` trait wraps it as `assertResponseMatchesOpenApiSpec($response, $method, $path)`; conformance coverage — `OpenApiConformanceFeatureTest` (18 tests) covers happy-path + canonical error shapes for every in-scope endpoint, `SpecAssertionHelperTest` (4 tests) locks parse + path coverage + ApiErrorCode parity; `OpenApiContractSourceGuardrailTest` (8 tests) locks spec existence, OpenAPI 3.0 declaration, structural validity, 14-path coverage, ApiErrorCode enum parity, composer dev dependency, helper/trait/conformance-test presence, and the two distinct error envelopes; no runtime contract changes (controllers, DTOs, middleware untouched); convergence waves `C0-C7` are the remaining roadmap surface |
| `2026-07-05` | `C0` closed: `docs/ARCHITECTURE.md` extended with `## Module Boundary Contract` (module public API = `app/Domains/<Module>/Contracts/`, interfaces + DTOs only, no Eloquent at boundary; cross-module imports through `<OtherModule>\Contracts\` only; always-allowed namespaces — shared kernel `App\Domain\*`, infra `App\Support\*`, and the enumerable legacy bridge list `App\Contracts\Application\Services\Repositories\Http.Models.Exceptions.Policies.Providers` + `Database\Factories\Seeders` that only shrinks; relocation mechanics — namespace move per slice, no dual-namespace shims, no `class_alias`, atomic with route/DI/test updates; provider policy — each module ships a `<Module>ServiceProvider` from `C1` onward); `tests/Unit/Architecture/ModuleBoundaryGuardrailTest.php` (5 tests) — namespace-aware use-statement scanner enforcing cross-module Contracts-only imports, enumerable legacy-bridge allowlist asserted against documented set, module-internal layer direction (controllers don't depend on Services/Repositories directly, application handlers don't import HTTP transport, repositories stay persistence-only); `docs/REPO_MAP.md` extended under `## Target layout` with per-module ownership table (Module / Public API / Owning wave / Migration state); `docs/DOMAIN_MAP.md` extended with `## Module Boundary Contract` cross-reference and per-context migration-state markers (`[migration: pending C1]` through `[migration: pending C7]`) plus a new `### Payments` H3 surfacing the `C6` gateway-contract migration target; risk register entries added for cross-context service coupling and the contract-bridge migration surface; exit target #25 marked achieved; execution-queue rows split — `C0` closed, `C1-C7` remain pending promotion; guardrail passes trivially today (empty `app/Domains/*`) and becomes load-bearing with `C1`; no runtime code moves (controllers, services, repositories untouched); `C1-C7` are the remaining roadmap surface |
| `2026-07-05` | `C1` closed: first runtime module move executed. Public catalog read slice relocated into `app/Domains/Catalog/*` with namespace moves and zero dual-namespace shims — `Controllers/CatalogController` (now `final`) + `CatalogIndexRequest`; `Application/Queries/` (3 handlers + 2 payloads); `Application/Dto/` (7 module-internal DTOs); `Services/CatalogService` + `Services/CatalogVersionService`; `Repositories/CatalogProductReadRepository`; new `CatalogServiceProvider` registered in `bootstrap/providers.php`. Module public API in `Contracts/` — `CatalogProductReadRepository` (keeps `Product` at boundary as a documented shared-kernel allowance pending the model-ownership wave), `CatalogCacheVersion` (`current()`/`bump()` consumed by 3 admin services for cache invalidation), `CatalogReadService` (`list`/`productBySlug`/`categories` consumed by handlers and performance-smoke scenarios), `Dto/CatalogProductListFilterDto` (consumed by smoke factory + context DTO). Atomic wiring — `routes/api.php` import switched; `ApplicationBindingsServiceProvider` catalog binding moved to `CatalogServiceProvider`; 3 admin services now depend on `CatalogCacheVersion` contract; 2 performance-smoke scenarios now depend on `CatalogReadService` contract; `psalm.xml` per-file suppression relocated to the new `CatalogService` path and `app/Domains/Catalog/Repositories` added to both `TooManyTemplateParams` and `InvalidDocblock` suppressions. Tests — `CatalogModuleRelocationTest` (new, 2 tests) locks contract bindings + route namespace; `SpaShellCacheTest` (new) extracted from `CatalogTest`; `CatalogVersionServiceTest` resolves via contract; `ApplicationRepositoryBindingTest` catalog row updated; `RepositoryReadBoundaryTest` and `RepositoryBusinessDecisionBoundaryTest` updated (catalog-product entries removed/updated post-move). `/api/v1/catalog/*` wire contract byte-identical (verified by `OpenApiConformanceFeatureTest`); `catalog:version` cache key unchanged; admin write behavior unchanged (only constructor types moved from concrete to contract); Eloquent models stay shared under the `App\Models\` legacy-bridge allowance; `ModuleBoundaryGuardrailTest` continues to pass; performance-smoke query-budget gates still green. The C0 boundary contract proved load-bearing on the first runtime move; the legacy-bridge allowlist effectively shrinks by one concrete service class; `C2-C7` are the remaining roadmap surface |
| `2026-07-05` | `C2` closed: second runtime module move executed. Auth + Account bounded context relocated into `app/Domains/Users/*` with namespace moves and zero dual-namespace shims — `Controllers/` (4 controllers — `AuthController`, `PasswordController`, `VerificationController` made `final` on move; `AccountOrdersController` already `final`; + 6 FormRequests, 4 made `final`); `Application/Commands/` (8 Auth command handlers + 8 payloads), `Application/Queries/` (5 query handlers + 5 payloads — 1 Auth + 4 Account Orders), `Application/Dto/` (7 Auth DTOs + 11 Account Orders DTOs); `Application/AuthAccessTokenIssuer` + `AuthActiveUserRevalidator` + `AuthApplicationException`; `Support/` (5 Auth classes — `AuthAuditContext`, `AuthAuditContextResolver`, `AuthAuditEvent` enum, `AuthLoginRateLimitKey`, `AuthUserDtoMapper`; + `AccountOrderSummaryProjector`); `Contracts/` (4 interfaces — `AuthUserRepository`, `AuthPasswordBrokerRepository`, `AuthAuditLogger`, `AccountOrderReadRepository`; all module-internal — no other module imports them today, so no new contract files needed); `Repositories/` (3 implementations); `Middleware/EnsureActiveApiUser` (alias `active.api.user` preserved; FQCN updated in `bootstrap/app.php`); `Infrastructure/ObservabilityAuthAuditLogger`. `AuthBindingsServiceProvider` renamed to `UsersServiceProvider`, moved into the module, and absorbed the `AccountOrderReadRepository` binding previously owned by `ApplicationBindingsServiceProvider`; `UsersServiceProvider` registered in `bootstrap/providers.php`. Atomic wiring — `routes/api.php` imports updated (9 auth + 4 account/orders + 2 legacy `orders/me` aliases); `psalm.xml` per-directory suppressions extended for `app/Domains/Users/Repositories`, `app/Domains/Users/Application/Dto`, and `app/Domains/Users/Contracts/AccountOrderReadRepository.php`. Tests — 9 unit tests relocated to `tests/Unit/Application/Users/`; `UsersModuleRelocationTest` (new, 4 tests) locks the 4 contract bindings + auth controller namespace + middleware alias FQCN + verification route names; 6 feature tests (`AuthFlowTest`, `ProfileUpdateTest`, `PasswordResetFlowTest`, `EmailVerificationTest`, `AuthAuditTrailFeatureTest`, `AccountOrdersApiTest`) reference the new namespaces; `AuthAuditEmissionGuardrailTest` repository scan extended to `app/Domains/Users/Repositories`; `RepositoryReadBoundaryTest`/`RepositoryBusinessDecisionBoundaryTest` updated (Account order paths moved); `ApplicationAuthRepositoryBoundaryTest` handler FQCN literals and discovery directories updated; `SecurityConfigGuardrailTest`/`AuthTokenLifecycleGuardrailTest`/`AuthCredentialHardeningGuardrailTest`/`InfrastructureProviderBoundaryTest` namespace literals updated. Operational contracts preserved byte-identical — `AuthAuditEvent` enum literal values (8 telemetry keys), `DUMMY_PASSWORD_HASH` timing-attack mitigation, sanctum token expiration, `auth.login` throttle, `Password::defaults()`, route names `verification.verify`/`verification.send`, `active.api.user` alias; `/api/v1/auth/*` and `/api/v1/account/orders/*` HTTP contracts locked by `docs/api/openapi.yaml` (S1) and verified by `OpenApiConformanceFeatureTest`. Shared `ResolvesAuthenticatedUser` trait stays at `app/Http/Controllers/Concerns/` (used cross-module by Cart/Checkout — C3/C4 will consolidate); shared `AppliesOrderSearch` trait stays at `app/Repositories/Concerns/`; Eloquent models (`User`, `Order`, `OrderItem`, `Payment`, `Shipment`) stay shared under the `App\Models\` legacy-bridge allowance pending the post-C7 model-ownership wave. Frontend untouched (URL-only coupling). Zero smoke/admin class coupling (unlike C1, no Users-module contract is consumed by smoke factories). The C0 boundary contract remains green; the legacy-bridge allowlist effectively shrinks further (Auth + Account namespaces no longer in legacy paths); exit target #26 fully achieved (both Catalog C1 and Users C2); `C3-C7` are the remaining roadmap surface |


