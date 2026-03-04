<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto;
use App\Application\Checkout\Dto\CheckoutPlaceOrderResultDto;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Payment\PaymentService;

final readonly class CheckoutPlaceOrderOrchestrator
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService,
        private PaymentService $paymentService,
    ) {}

    public function place(
        CheckoutPlaceOrderInputDto $checkoutInput,
        string $idempotencyKey,
        ?User $user,
    ): CheckoutPlaceOrderResultDto {
        $guestToken = $checkoutInput->guestToken ?? '';

        if ($user !== null && $guestToken !== '') {
            $this->cartService->mergeGuestCart($user, $guestToken);
        }

        $cart = $this->cartService->resolveForCheckout(
            $user,
            $guestToken === '' ? null : $guestToken,
        );

        $order = $this->checkoutService->placeOrder(
            $cart,
            $checkoutInput,
            $idempotencyKey,
            $user,
        );

        $payment = $this->paymentService->initiate($order, 'checkout-'.$idempotencyKey);

        return CheckoutPlaceOrderResultDto::fromModels($order, $payment);
    }
}
