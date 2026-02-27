<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Application\Checkout\Dto\CheckoutInventoryDemandDto;
use App\Application\Checkout\Dto\CheckoutOrderWriteInputDto;
use App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto;
use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class CheckoutService
{
    public function __construct(
        private readonly CheckoutDiscountResolver $checkoutDiscountResolver,
        private readonly CheckoutIdempotencyGuard $checkoutIdempotencyGuard,
        private readonly CheckoutInventoryAllocator $checkoutInventoryAllocator,
        private readonly CheckoutCartPreparer $checkoutCartPreparer,
        private readonly CheckoutOrderWriter $checkoutOrderWriter,
    ) {}

    /**
     * Create order from cart with idempotency key.
     */
    public function placeOrder(
        Cart $cart,
        CheckoutPlaceOrderInputDto $checkoutInput,
        string $idempotencyKey,
        ?User $user = null,
    ): Order {
        $scopeKey = $this->resolveScopeKey($cart, $user);
        $hash = hash('sha256', json_encode([$cart->id, $checkoutInput->toHashPayload()], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($cart, $checkoutInput, $idempotencyKey, $hash, $scopeKey, $user): Order {
            $lockedCart = Cart::query()
                ->whereKey($cart->id)
                ->with(['items.variant.product'])
                ->lockForUpdate()
                ->first();

            if (! $lockedCart instanceof Cart) {
                throw new DomainException('Cart not found.');
            }

            $idempotencyResolution = $this->checkoutIdempotencyGuard->resolve(
                lockedCart: $lockedCart,
                scopeKey: $scopeKey,
                idempotencyKey: $idempotencyKey,
                requestHash: $hash,
            );

            if ($idempotencyResolution->existingOrder instanceof Order) {
                return $idempotencyResolution->existingOrder;
            }

            $idempotency = $idempotencyResolution->idempotency;

            $cartPreparation = $this->checkoutCartPreparer->prepare($lockedCart);

            $this->checkoutInventoryAllocator->assertAndConsume(
                CheckoutInventoryDemandDto::fromRequiredQuantityMap($cartPreparation->requiredQuantityByVariant)
            );

            $discountContext = $this->checkoutDiscountResolver->resolve($checkoutInput, $cartPreparation->subtotal);
            $discountTotal = $discountContext->discountTotal;
            $shippingTotal = 0.0;
            $order = $this->checkoutOrderWriter->write(new CheckoutOrderWriteInputDto(
                cart: $lockedCart,
                checkoutInput: $checkoutInput,
                user: $user,
                cartPreparation: $cartPreparation,
                discountTotal: $discountTotal,
                shippingTotal: $shippingTotal,
            ));

            $lockedCart->update(['status' => 'checked_out']);

            if ($discountContext->coupon !== null) {
                $discountContext->coupon->increment('redeemed_count');
            }

            if ($discountContext->promotion !== null) {
                $discountContext->promotion->increment('usage_count');
            }

            $idempotency->update([
                'cart_id' => $lockedCart->id,
                'request_hash' => $hash,
                'order_id' => $order->id,
                'expires_at' => now()->addHours(24),
            ]);

            event(new OrderPlaced($order));

            return $order->fresh(['items', 'payments', 'shipments']);
        });
    }

    /**
     * Build checkout idempotency scope key.
     */
    private function resolveScopeKey(Cart $cart, ?User $user): string
    {
        if ($user !== null) {
            return 'user:'.$user->id;
        }

        if (! empty($cart->guest_token)) {
            return 'guest:'.$cart->guest_token;
        }

        throw new DomainException('Guest checkout requires guest token.');
    }
}
