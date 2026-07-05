# Checkout Domain Module

The Checkout module owns the place-order + pay-for-order HTTP surface,
including idempotent cart-to-order conversion, money arithmetic (subtotal,
discount, shipping, total), inventory deduction, and `OrderPlaced` dispatch.
It depends on Cart (`CartServiceInterface` cross-module) and Payment
(`PaymentService` legacy bridge until C6).

## Active subfolders

- `Contracts/` — module public API (2 interfaces); see below.
- `Controllers/` — transport: `CheckoutController` (`placeOrder`, `pay`) + 2 FormRequests.
- `Application/Commands/` — `PlaceCheckoutOrderHandler` + `PlaceCheckoutOrderCommand`, `InitiateCheckoutPaymentHandler` + `InitiateCheckoutPaymentCommand`.
- `Application/Dto/` — 12 DTOs (input + result DTOs for the place-order + pay flows; `CheckoutAddressInputDto` is the closed-shape address boundary locked by `AddressPayloadBoundaryGuardrailTest`).
- `Services/` — 9 service classes (`CheckoutService`, `CheckoutPlaceOrderOrchestrator`, `CheckoutOrderWriter`, `CheckoutOrderFinalizer`, `CheckoutIdempotencyGuard`, `CheckoutDiscountResolver`, `FreeCheckoutShippingCostResolver`, `CheckoutInventoryAllocator`, `CheckoutCartPreparer`, `CheckoutRequestIdentityResolver`).
- `Services/Dto/` — 4 internal service DTOs (`CheckoutRequestIdentityDto`, `CheckoutDiscountContextDto`, `CheckoutOrderFinalizationInputDto`, `CheckoutIdempotencyResolutionDto`).
- `Middleware/EnsureIdempotencyKeyMiddleware` — HTTP-level idempotency-key enforcement (alias `idempotency.key`); requires non-empty `Idempotency-Key` header on `place-order` + `pay` routes.
- `CheckoutServiceProvider.php` — binds the 2 contracts; owns `RateLimiter::for('checkout', ...)` (6/min by user or IP); registered in `bootstrap/providers.php`.

The `Models/` subfolder is deferred to the post-C7 model-ownership wave.
Eloquent models (`Order`, `OrderItem`, `Payment`, `Shipment`, `CheckoutIdempotency`)
stay shared in `app/Models/*` and are imported by this module under the C0
legacy-bridge allowance. The `OrderStatus` / `PaymentStatus` / `ShipmentStatus`
enums stay in `App\Enums\` (heavy cross-module usage: cart, admin, webhooks,
listeners, smoke, factories). `CheckoutException` stays in
`App\Domain\Exceptions\` (shared kernel). `OrderPlaced` event stays at
`App\Events\` (listened to cross-module by `QueueOrderSideEffects`).

## Public contract surface

`App\Domains\Checkout\Contracts\`

- `CheckoutServiceInterface` — module public API (1 method, `placeOrder`). Consumed cross-module by smoke scenarios (`CheckoutPlaceOrderPerformanceSmokeScenario`, `WebhookFlowScenario`) and intra-module by `CheckoutPlaceOrderOrchestrator`. Returns `Order` at the boundary as a documented shared-kernel allowance pending the model-ownership wave.
- `CheckoutShippingCostResolver` — module-internal extension point (1 method, `resolve`); implementations registered through DI. Default implementation is `FreeCheckoutShippingCostResolver` (zero shipping). Promoted from interface-in-services pattern during the C4 relocation for cleaner ownership.

This is the second module in the convergence waves (after C3 Cart) that
publishes a contract surface with established cross-module consumers. The
existing Cart → Checkout coupling is one-way: Checkout imports
`App\Domains\Cart\Contracts\CartServiceInterface` (rewired in C3); Cart does
not import from Checkout.

## Operational contracts

- HTTP wire (2 endpoints):
  - `POST /api/v1/checkout/place-order` — returns `201` with order+payment envelope; middleware `['active.api.user', 'throttle:checkout', 'idempotency.key']`.
  - `POST /api/v1/checkout/orders/{order}/pay` — returns order+payment envelope; middleware `['throttle:checkout', 'idempotency.key']` (inside `auth:sanctum` + `active.api.user` group).
  - Locked by `tests/Feature/{CartCheckout,GuestCheckout,CheckoutAuthenticatedToken,CouponCheckout}Test.php`.
- Idempotency semantics:
  - `Idempotency-Key` header required (HTTP middleware enforces presence + trims).
  - Application-level `CheckoutIdempotencyGuard` resolves 5 branches: new record / expired / payload-mismatch (throws `CheckoutException::idempotencyPayloadMismatch`) / existing order (returns it) / cart-mismatch / continue.
  - `scope_key` = `user:<id>` or `guest:<token>` (throws `CheckoutException::guestTokenRequired` if neither).
  - Retention windows: `pending_minutes=30` (env `CHECKOUT_IDEMPOTENCY_PENDING_MINUTES`, max 10080) for unresolved records, `completed_hours=24` (env `CHECKOUT_IDEMPOTENCY_COMPLETED_HOURS`, max 720) for replayed finalized records.
- Rate limiting: `throttle:checkout` = 6/min by user-id or IP — registered in `CheckoutServiceProvider::boot()`.
- Money semantics: subtotal, discount_total, shipping_total, total flow as `decimal:2` strings at the Eloquent boundary. R2 promotion arithmetic (percent/fixed/capped/defensive) locked by `CheckoutIdempotencyAndPromotionArithmeticGuardrailTest`.
- Address payload boundary: `{line1, city, country, postcode}` closed shape, locked by `AddressPayloadBoundaryGuardrailTest`.
- Initial state tuple: `Order{status: pending, payment_status: pending, shipment_status: pending}` via `forceFill` in `CheckoutOrderWriter` (NOT mass assignment — `Order`/`Payment`/`Shipment` status fields excluded from `$fillable` per `SensitiveStateFillableGuardrailTest`).
- Locking semantics: `Cart::lockForUpdate()` + `CheckoutIdempotency::lockForUpdate()` + `Inventory::lockForUpdate()` + `Coupon::lockForUpdate()` + `Promotion::lockForUpdate()` all within `DB::transaction()` in `CheckoutService::placeOrder`.
- Event dispatch: `OrderPlaced` dispatched via `event()` inside `CheckoutOrderFinalizer::finalize()`, implements `ShouldDispatchAfterCommit`. Side-effect jobs (`DispatchShipmentJob`, etc.) dispatch via `->afterCommit()` from listeners in `EventServiceProvider`.
- Cart transition: `CartStatus::CHECKED_OUT` set by `CheckoutOrderFinalizer::finalize()` via `$cart->update(...)` (Cart `status` is fillable — unlike Order/Payment/Shipment).
- Relocation wired by `tests/Feature/CheckoutModuleRelocationTest.php`.

## Migration state

Complete (Wave C4, `2026-07-05`). The Checkout bounded context (place-order
+ pay-for-order HTTP surface, application layer, services, contracts, and
idempotency middleware) moved into this module as a single slice.
Cross-module consumers (smoke scenarios, smoke infra) now import checkout
contracts through `App\Domains\Checkout\Contracts\*`. The `CheckoutPlaceOrderOrchestrator`
retains its cross-module Cart dependency (the canonical C3 pattern) and its
legacy-bridge Payment dependency (will be re-evaluated in C6).

Out of scope for C4 (subsequent waves):
- **Webhooks** (`PaymentWebhookController`, `ShippingWebhookController`, `Application/Webhook/*`, `Services/Webhook/*`, `ProcessPaymentWebhookJob`) — Wave C7.
- **Order lifecycle / transition policies** (`OrderStatusTransitionPolicy`, `AdminOrderService`, `OrderInventoryReleaseService`, account/admin order reads) — Wave C5.
- **Payment slice** (`PaymentService`, `PaymentStatusTransitionPolicy`, gateway contracts) — Wave C6.
- **Shipping slice** (`ShippingService`, `ShipmentStatusTransitionPolicy`) — Wave C6/C7.

The shared `ResolvesAuthenticatedUser` trait stays at
`app/Http/Controllers/Concerns/` per C0 legacy-bridge allowance (used
cross-module by Users, Cart, and Checkout controllers). Eloquent models,
enums, events, and `CheckoutException` stay shared under their respective
legacy-bridge allowances pending the post-C7 model-ownership wave.
