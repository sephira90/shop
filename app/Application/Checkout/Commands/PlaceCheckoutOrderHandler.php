<?php

declare(strict_types=1);

namespace App\Application\Checkout\Commands;

use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Payment\PaymentService;

final class PlaceCheckoutOrderHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Execute place-order flow and return order + payment.
     */
    public function handle(PlaceCheckoutOrderCommand $command): PlaceCheckoutOrderResult
    {
        $guestToken = $command->guestToken();

        if ($command->user !== null && $guestToken !== '') {
            $this->cartService->mergeGuestCart($command->user, $guestToken);
        }

        $cart = $this->cartService->resolveForCheckout(
            $command->user,
            $guestToken === '' ? null : $guestToken,
        );
        $order = $this->checkoutService->placeOrder(
            $cart,
            $command->payload,
            $command->idempotencyKey,
            $command->user,
        );
        $payment = $this->paymentService->initiate($order, 'checkout-'.$command->idempotencyKey);

        return new PlaceCheckoutOrderResult($order, $payment);
    }
}
