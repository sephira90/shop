<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Contracts;

use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderInputDto;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;

interface CheckoutServiceInterface
{
    /**
     * Create order from cart with idempotency key.
     */
    public function placeOrder(
        Cart $cart,
        CheckoutPlaceOrderInputDto $checkoutInput,
        string $idempotencyKey,
        ?User $user = null,
    ): Order;
}
