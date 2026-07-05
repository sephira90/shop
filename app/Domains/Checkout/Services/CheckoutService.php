<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Services;

use App\Domain\Exceptions\CheckoutException;
use App\Domains\Checkout\Application\Dto\CheckoutInventoryDemandDto;
use App\Domains\Checkout\Application\Dto\CheckoutOrderWriteInputDto;
use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderInputDto;
use App\Domains\Checkout\Contracts\CheckoutServiceInterface;
use App\Domains\Checkout\Contracts\CheckoutShippingCostResolver;
use App\Domains\Checkout\Services\Dto\CheckoutOrderFinalizationInputDto;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CheckoutService implements CheckoutServiceInterface
{
    public function __construct(
        private readonly CheckoutRequestIdentityResolver $checkoutRequestIdentityResolver,
        private readonly CheckoutDiscountResolver $checkoutDiscountResolver,
        private readonly CheckoutIdempotencyGuard $checkoutIdempotencyGuard,
        private readonly CheckoutInventoryAllocator $checkoutInventoryAllocator,
        private readonly CheckoutCartPreparer $checkoutCartPreparer,
        private readonly CheckoutShippingCostResolver $checkoutShippingCostResolver,
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
                throw CheckoutException::cartNotFound();
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

            $cartPreparation = $this->checkoutCartPreparer->prepare($lockedCart, $checkoutInput->currency);

            $this->checkoutInventoryAllocator->assertAndConsume(
                CheckoutInventoryDemandDto::fromRequiredQuantityMap($cartPreparation->requiredQuantityByVariant)
            );

            $discountContext = $this->checkoutDiscountResolver->resolve($checkoutInput, $cartPreparation->subtotal);
            $discountTotal = $discountContext->discountTotal;
            $shippingTotal = $this->checkoutShippingCostResolver->resolve($lockedCart, $checkoutInput);
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
