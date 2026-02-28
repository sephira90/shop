<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Application\Checkout\Dto\CheckoutInventoryDemandDto;
use App\Application\Checkout\Dto\CheckoutOrderWriteInputDto;
use App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Services\Checkout\Dto\CheckoutOrderFinalizationInputDto;
use DomainException;
use Illuminate\Support\Facades\DB;

final class CheckoutService
{
    public function __construct(
        private readonly CheckoutRequestIdentityResolver $checkoutRequestIdentityResolver,
        private readonly CheckoutDiscountResolver $checkoutDiscountResolver,
        private readonly CheckoutIdempotencyGuard $checkoutIdempotencyGuard,
        private readonly CheckoutInventoryAllocator $checkoutInventoryAllocator,
        private readonly CheckoutCartPreparer $checkoutCartPreparer,
        private readonly CheckoutOrderWriter $checkoutOrderWriter,
        private readonly CheckoutOrderFinalizer $checkoutOrderFinalizer,
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
        $requestIdentity = $this->checkoutRequestIdentityResolver->resolve($cart, $checkoutInput, $user);

        return DB::transaction(function () use ($cart, $checkoutInput, $idempotencyKey, $requestIdentity, $user): Order {
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
                scopeKey: $requestIdentity->scopeKey,
                idempotencyKey: $idempotencyKey,
                requestHash: $requestIdentity->requestHash,
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

            return $this->checkoutOrderFinalizer->finalize(new CheckoutOrderFinalizationInputDto(
                lockedCart: $lockedCart,
                order: $order,
                idempotency: $idempotency,
                discountContext: $discountContext,
                requestHash: $requestIdentity->requestHash,
            ));
        });
    }
}
