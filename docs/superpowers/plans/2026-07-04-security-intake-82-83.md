# Security Intake Items 82/83 — Data-at-Rest Minimization + Security Guardrails

**Status:** Active — execution starts after this plan is approved.
**Scope owner:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` (item 10 in Execution Queue, P2).
**Source findings:** `docs/DEEP_ARCHITECTURE_AUDIT_2026_03_V2.md` §Security Intake — P2 (items 82, 83).
**Convergence impact:** additive only — no breaking API/storage change; introduces inventory, defensive guards, and a unified security-config guardrail.

## Verified baseline

- `Order::$fillable` exposes `billing_address`, `shipping_address`, `cart_snapshot` (JSON, plaintext). The checkout path builds these via `CheckoutAddressInputDto::toArray()`, which emits exactly four keys: `line1`, `city`, `country`, `postcode`. No `phone`/`email`/free-form notes can reach the column through the validated checkout flow.
- `Payment::payload` is sourced from `PaymentCreationResultDto::payload` produced by `PaymentGatewayInterface` implementations. The repository's only adapter (`FakePaymentGateway`) emits a closed key set (`provider`, `idempotency_key`, `order_number`, `amount`, `currency`, `checkout_url`) — no cardholder data, no PII beyond order reference.
- `Shipment::payload` follows the same shape via `ShipmentCreationResultDto::payload`.
- Security config is partially guarded today by point guardrails: `AuthTokenLifecycleGuardrailTest` (finite Sanctum expiration, `active.api.user` middleware coverage), `AuthCredentialHardeningGuardrailTest` (login limiter, password defaults), `AuthAuditEmissionGuardrailTest`, `SensitiveStateFillableGuardrailTest` (80/81), `TransportSecurityBaselineGuardrailTest` (80/81), `ApiErrorCodeStabilityGuardrailTest` (R1). There is no single guardrail asserting the cross-cutting security-config invariants as a program.

## Invariants to lock

1. **Address payload is a closed shape.** Any code path that constructs `billing_address` / `shipping_address` for persistence must emit a subset of `{line1, city, country, postcode}`. Rejected keys: `phone`, `email`, `notes`, `recipient_name`, and any free-form personal field.
2. **Gateway payload is provider-metadata only.** `Payment::payload` / `Shipment::payload` must not contain card numbers, CVV, full names, or raw customer PII. Allowed shape: provider-scoped operational keys (transaction id, idempotency key, status code, amounts, checkout URL, provider event id).
3. **Security config stays aligned across environments.** The unified guardrail asserts the security-aligned defaults that today live in `config/sanctum.php`, `config/auth.php`, `config/session.php`, `config/security.php`, `config/cors.php`, route middleware wiring, and the credential-hardening request layer — as a single source of truth, so a future drift in any one file fails the gate.
4. **Inventory is explicit and reviewed.** A `docs/SECURITY_DATA_CLASSIFICATION.md` document records the data classes, their storage columns, their allowed key sets, and the follow-up roadmap entry for field-level encryption.

## Out of scope (deliberate)

- Field-level encryption / encrypted casts on `billing_address` / `payload`. Promoted to a follow-up roadmap item (audit item 82 explicitly names "field-level encryption or encrypted casts" as a candidate; this block prepares the classification inventory and defensive key boundary so the encryption follow-up has a stable surface).
- Backfill migrations rewriting existing rows.
- Changes to the `/api/v1/*` response envelope or DTO shapes returned to clients.
- Real-provider payment/shipping drivers (audit item 86 — separate decision).

## Implementation slices

### Slice 1 — Address payload boundary (item 82, defensive)

- **RED 1:** `tests/Unit/Architecture/AddressPayloadBoundaryGuardrailTest` asserts the address DTO emits exactly `{line1, city, country, postcode}` and that `CheckoutOrderWriter`, `PerformanceSmokeSetupFactory`, `WebhookFlowScenario`, `CheckoutApiContractScenario` construct addresses through the typed DTO (or, for smoke fixtures only, an explicit allowlist shape). The test fails today because the boundary is implicit.
- **GREEN 1:** tighten `CheckoutAddressInputDto::toArray()` contract via a `@return` shape already in place; add an architectural assertion that any `Order::query()->create([... 'billing_address' => ...])` call site in `app/` routes through the DTO. Smoke fixtures that build raw arrays are constrained to the allowlist shape through a shared `AddressPayload::billing()/shipping()` helper under `app/Support/Data/`.

### Slice 2 — Gateway payload boundary (item 82, defensive)

- **RED 2:** `tests/Unit/Architecture/GatewayPayloadBoundaryGuardrailTest` asserts `PaymentGatewayInterface` and `ShippingGatewayInterface` implementations in `app/Infrastructure/` build payloads via `JsonPayload` and reference the allowlisted operational keys only; rejects literals such as `card`, `cvv`, `pan`, `ssn`, `password`.
- **GREEN 2:** no production change expected (FakePaymentGateway already conforms); the guardrail exists to lock the boundary for future real-provider adapters.

### Slice 3 — Data classification inventory (item 82)

- **Doc:** create `docs/SECURITY_DATA_CLASSIFICATION.md` with: (a) inventory of PII-bearing columns (`orders.billing_address`, `orders.shipping_address`, `orders.email`, `payments.payload`, `shipments.payload`, `users.email`, `users.phone`, `users.password`); (b) allowed key sets for JSON columns; (c) threat model summary (plaintext-at-rest blast radius); (d) follow-up roadmap pointer for field-level encryption.
- **Guardrail:** `tests/Unit/Architecture/SecurityDataClassificationDocGuardrailTest` asserts the doc exists, names each PII column, and references the encryption follow-up — preventing silent drift.

### Slice 4 — Unified SecurityConfigGuardrailTest (item 83)

- **RED 4:** `tests/Unit/Architecture/SecurityConfigGuardrailTest` fails today. It aggregates assertions currently spread across `AuthTokenLifecycleGuardrailTest`, `AuthCredentialHardeningGuardrailTest`, `TransportSecurityBaselineGuardrailTest` into a single programmatic contract:
  - Sanctum `expiration` is a positive integer (already asserted elsewhere — restate for the unified view).
  - `auth.login_throttle.max_attempts` and `decay_seconds` are positive and bounded.
  - `config('session.secure')` resolves to a bool; in non-local `APP_ENV` it is `true` unless explicitly overridden via `SESSION_SECURE_COOKIE`.
  - `config('security.force_https')` is bool; `trusted_proxies` is a non-empty string.
  - `config('cors.allowed_origins')` is a list; in non-local `APP_ENV` it does not contain `'*'`.
  - Login route uses `throttle:auth.login`; every `auth:sanctum` api/v1 route also carries `active.api.user`.
- **GREEN 4:** no production change expected for assertions already covered by point guardrails; the new assertions (CORS non-wildcard in non-local, session.secure resolution, security.force_https resolution) lock the 80/81 baseline into the unified program.
- The point guardrails (`AuthTokenLifecycleGuardrailTest`, `AuthCredentialHardeningGuardrailTest`, `TransportSecurityBaselineGuardrailTest`) stay in place — they remain the per-contract authority. `SecurityConfigGuardrailTest` is the cross-cutting rollup.

### Slice 5 — Documentation + roadmap sync

- Update `docs/ARCHITECTURE_REFACTOR_NEXT.md`: mark `82/83` Closed, advance the next item to Active, append a Closed-block definition, update Risk Register (close 82/83 risks, add the encryption follow-up as a candidate), Exit Targets, Interface/Contract Changes, Mandatory Test Matrix, and Change Control.
- Append `82/83` entry to `docs/REFACTORING_EXECUTION_PLAN.md`.
- Update `docs/REPO_MAP.md` to reference `docs/SECURITY_DATA_CLASSIFICATION.md` and the new guardrails.
- Update `docs/AI_REPO_MAP.md` if the new guardrail files enter the agent-navigated surface.

### Slice 6 — Quality gate

- Run the canonical sequence strictly in order, one command at a time:
  1. `composer run lint`
  2. `composer run analyse`
  3. `php artisan test`
  4. `npm run lint`
  5. `npm run lint:ox`
  6. `npm run format:ox:check`
  7. `npm run type-check`
  8. `npm run test`
  9. `npm run build`
- Scope notes apply: routes/config guardrails unchanged → no `optimize:clear`/`route:list` step required; documentation guardrails run inside `php artisan test`.

## Definition of done

1. The address and gateway payload boundaries are locked by architecture guardrails.
2. `docs/SECURITY_DATA_CLASSIFICATION.md` exists and is itself guarded.
3. `SecurityConfigGuardrailTest` aggregates the security-config contract in one place.
4. `docs/ARCHITECTURE_REFACTOR_NEXT.md` records `82/83` as Closed with convergence impact and exit-target update; `docs/REFACTORING_EXECUTION_PLAN.md` records the executed work and checks.
5. The quality gate is green and the executed checks are reported.
