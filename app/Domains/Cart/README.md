# Cart Domain Module

The Cart module owns the active-cart read/mutate surface for both authenticated
users and guests. It exposes the cross-module contract consumed by Users
(login-time guest-cart merge) and Checkout (resolveForCheckout + merge), and
owns the cart authorization policy.

## Active subfolders

- `Contracts/` — module public API (2 interfaces); see below.
- `Controllers/` — transport: `CartController` (`show`, `upsertItem`, `removeItem`) + 2 FormRequests.
- `Application/Commands/` — `UpsertCartItemHandler` + `UpsertCartItemCommand`, `RemoveCartItemHandler` + `RemoveCartItemCommand`.
- `Application/Queries/` — `GetCurrentCartHandler` + `GetCurrentCartQuery`.
- `Application/Dto/` — 5 DTOs (`CartResultDto`, `CartItemResultDto`, `CartUpsertItemInputDto`, `RemoveCartItemInputDto`, `CartSummaryResultDto`).
- `Services/` — `CartService` (implements `CartServiceInterface`), `CartResolver`, `CartMutationService` (implements `CartMutationServiceInterface`), `CartResultMapper`.
- `Policies/` — `CartPolicy` (`viewAny`, `modify`); registered via `Gate::policy(Cart::class, CartPolicy::class)` in `CartServiceProvider::boot()`.
- `CartServiceProvider.php` — binds the 2 contracts; registers the policy; registered in `bootstrap/providers.php`.

The `Models/` subfolder is deferred to the post-C7 model-ownership wave.
Eloquent models (`Cart`, `CartItem`) stay shared in `app/Models/*` and are
imported by this module under the C0 legacy-bridge allowance. The
`CartStatus` enum stays in `App\Enums\` (heavy cross-module usage: checkout,
maintenance, admin, factories). `CartException` stays in
`App\Domain\Exceptions\` (shared kernel).

## Public contract surface

`App\Domains\Cart\Contracts\`

- `CartServiceInterface` — module public API (6 methods). Consumed cross-module by:
  - `App\Domains\Users\Application\Commands\LoginAuthUserHandler` (`mergeGuestCart` after authentication),
  - `App\Domains\Checkout\Services\CheckoutPlaceOrderOrchestrator` (after C4; `mergeGuestCart` + `resolveForCheckout`),
  - `App\Support\Smoke\Performance\Scenarios\{CartShowPerformanceSmokeScenario, CheckoutPlaceOrderPerformanceSmokeScenario}`,
  - `App\Support\Smoke\Performance\PerformanceSmokeSetupFactory`,
  - `App\Support\Smoke\WebhookFlow\WebhookFlowScenario`.
  Method surface: `resolve`, `resolveForCheckout`, `upsertItem`, `removeItem`, `mergeGuestCart`, `toResultDto`. Returns `CartResultDto` at the boundary as a documented shared-kernel allowance pending the model-ownership wave.
- `CartMutationServiceInterface` — module-internal mutation contract (3 methods). Consumed only by `CartService` (inside the module) and by tests; preserved as a contract for the existing test surface (`CartMutationSafetyTest` resolves via the contract).

This is the first module in the convergence waves that publishes a contract
surface with established cross-module consumers today. The Users → Cart
import in `LoginAuthUserHandler` is the first non-trivial
`Domains\<Other>\Contracts\` cross-module import exercised by
`ModuleBoundaryGuardrailTest`.

## Operational contracts

- HTTP wire (`docs/api/openapi.yaml`, S1): `GET /cart`, `POST /cart/items`, `DELETE /cart/items/{variantId}` — verified by `tests/Feature/OpenApiConformanceFeatureTest.php`.
- `CartStatus` enum literal values (`active`, `checked_out`, `abandoned`) — wire + DB + cross-module.
- Guest cart token resolution — `?guest_token=` query OR `X-Cart-Token` header (both channels work).
- `CartException` literal messages (`'Cart not found.'`, `'Cart ownership mismatch.'`, `'Selected variant is not available.'`, `'Insufficient stock for selected variant.'`, `'Guest token is required.'`, `'Authenticated user no longer exists.'`) mapped by `App\Support\Api\ApiExceptionRenderer` to HTTP status codes.
- Locking semantics — `lockForUpdate` on `Cart`, `CartItem`, `User` rows within `DB::transaction` (`CartResolver::resolve`/`resolveForCheckout`, `CartMutationService::upsertItem`/`removeItem`/`mergeGuestCart`). Locked by `tests/Feature/CartMutationSafetyTest.php`.
- Ownership guard semantics — `CartMutationService::assertOwnership()`. Authenticated user must match `cart.user_id`; guest must supply matching `guest_token`. Locked by `tests/Feature/CartMutationSafetyTest.php`.
- Maintenance cleanup resource strings (`'active_carts'`, `'inactive_carts'`), config keys (`cleanup.retention.active_cart_hours`, `cleanup.retention.inactive_cart_hours`), and CLI option names (`--active-cart-retain-hours`, `--inactive-cart-retain-hours`) — operational contract for ops/oncall; not moved by C3 (maintenance commands stay global).
- Relocation wired by `tests/Feature/CartModuleRelocationTest.php`.

## Migration state

Complete (Wave C3, `2026-07-05`). The Cart bounded context moved into this
module as a single slice. Cross-module consumers (Users login handler,
Checkout orchestrator, smoke scenarios) now import cart contracts through
`App\Domains\Cart\Contracts\*` — the first real cross-module
`Domains\<Other>\Contracts\` import exercised by `ModuleBoundaryGuardrailTest`.
The shared `ResolvesAuthenticatedUser` trait stays in legacy bridge per C0
(used cross-module by Users and Checkout controllers). Eloquent models
(`Cart`, `CartItem`), `CartStatus` enum, and `CartException` stay shared
under their respective legacy-bridge allowances pending the model-ownership
wave.
