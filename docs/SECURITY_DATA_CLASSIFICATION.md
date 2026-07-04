# Security Data Classification Inventory

**Authority:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` (security intake item 82, closed `2026-07-04`).
**Scope:** inventory of personally identifiable information (PII) and sensitive operational data persisted by the application, with the allowed key sets for JSON columns and the follow-up roadmap pointer for field-level encryption. This document is the source of truth referenced by `tests/Unit/Architecture/SecurityDataClassificationDocGuardrailTest.php`.

## Storage columns holding PII or sensitive operational data

| Table | Column | Type | Class | Notes |
| --- | --- | --- | --- | --- |
| `users` | `email` | `string(255)` | Contact PII | Login identity; uniqueness enforced. |
| `users` | `phone` | `string(32)` | Contact PII | Optional; collected via registration/profile flows. |
| `users` | `password` | `string(255)` | Secret | Bcrypt/argon2 hash; never logged or serialized. |
| `users` | `first_name`, `last_name` | `string(80)` | Identity PII | Optional. |
| `users` | `remember_token` | `string(100)` | Secret | Session-scoped; rotated on login/logout. |
| `orders` | `email` | `string(255)` | Contact PII | Order contact email (guest checkout allowed). |
| `orders` | `billing_address` | `json` | Address PII (closed shape) | See allowlist below. |
| `orders` | `shipping_address` | `json` | Address PII (closed shape) | See allowlist below. |
| `orders` | `cart_snapshot` | `json` | Operational | Catalog snapshot at purchase; no PII. |
| `payments` | `transaction_id` | `string(120)` | Operational | Provider-issued reference. |
| `payments` | `payload` | `json` | Provider operational (closed shape) | See allowlist below. |
| `shipments` | `tracking_number` | `string(120)` | Operational | Carrier-issued reference. |
| `shipments` | `payload` | `json` | Provider operational (closed shape) | See allowlist below. |

## Allowed key sets for JSON columns

The closed shapes below are enforced by architecture guardrails so future adapters cannot widen the persisted surface.

### `orders.billing_address`, `orders.shipping_address`

Allowed keys: `line1`, `city`, `country`, `postcode`.

Rejected (must never enter the address blob): `phone`, `email`, `notes`, `recipient_name`, `full_name`, `card`, `card_number`, `cvv`, `cvc`, `pan`, `ssn`.

Construction is routed through `App\Application\Checkout\Dto\CheckoutAddressInputDto::toArray()`. Enforcement: `tests/Unit/Architecture/AddressPayloadBoundaryGuardrailTest.php`.

### `payments.payload`

Allowed operational keys: `provider`, `idempotency_key`, `order_number`, `amount`, `currency`, `checkout_url`, `transaction_id`, `status`, `event_id`, and provider-scoped operational references introduced by a concrete adapter.

Rejected (must never enter the gateway payload): card numbers, CVV/CVC, PAN, SSN, customer passwords, raw customer PII, free-form recipient names.

Construction is routed through `App\Support\Data\JsonPayload` from `PaymentGatewayInterface::createPayment()`. Enforcement: `tests/Unit/Architecture/GatewayPayloadBoundaryGuardrailTest.php`.

### `shipments.payload`

Allowed operational keys: `provider`, `order_id`, `tracking_number`, `status`, `event_id`, and carrier-scoped operational references introduced by a concrete adapter.

Rejected: same set as `payments.payload`.

Construction is routed through `JsonPayload` from `ShippingGatewayInterface::createShipment()`. Enforcement: `tests/Unit/Architecture/GatewayPayloadBoundaryGuardrailTest.php`.

## Threat model summary (plaintext-at-rest)

The columns above are stored as plaintext JSON or plaintext strings. The blast radius of a database leak includes customer contact data, order addresses, and provider operational references. Application-level controls (mass-assignment hardening on sensitive state fields, transport security baseline, finite Sanctum token TTL, active-user revalidation on every authenticated route, login throttling, webhook signature verification, idempotency-key replay protection) limit the reachable surface, but they do not reduce the at-rest blast radius.

## Follow-up: field-level encryption

Field-level encryption of `orders.billing_address`, `orders.shipping_address`, and the provider `payload` columns is deferred to a separate roadmap item. Prerequisites already in place after intake items `82/83`:

- closed-shape JSON contracts enforced by architecture guardrails (stable surface for encrypted casts);
- central data classification inventory (this document) referenced by a guardrail so the surface stays classified;
- `App\Support\Data\JsonPayload` abstraction at every construction site (single point for future cast injection).

The encryption follow-up must, before any code change, declare: encryption key management (`APP_ENCRYPTION_KEY` lifecycle, rotation story), backfill migration plan for existing rows, query-shape impact (encrypted columns are not queryable), and a rollback path. Promotion of that follow-up requires roadmap approval per the `AGENTS.md` change discipline.
