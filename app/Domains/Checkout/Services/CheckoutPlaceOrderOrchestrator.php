<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Services;

use App\Domains\Cart\Contracts\CartServiceInterface;
use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderInputDto;
use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderResultDto;
use App\Domains\Checkout\Contracts\CheckoutServiceInterface;
use App\Models\User;
use App\Services\Payment\PaymentService;

final readonly class CheckoutPlaceOrderOrchestrator
{
    public function __construct(
        private CartServiceInterface $cartService,
        private CheckoutServiceInterface $checkoutService,
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
