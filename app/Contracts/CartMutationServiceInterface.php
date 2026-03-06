<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Cart;
use App\Models\User;

interface CartMutationServiceInterface
{
    /**
     * Add or update cart item.
     */
    public function upsertItem(
        Cart $cart,
        int $variantId,
        int $quantity,
        ?User $user = null,
        ?string $guestToken = null,
    ): Cart;

    /**
     * Remove item from cart.
     */
    public function removeItem(
        Cart $cart,
        int $variantId,
        ?User $user = null,
        ?string $guestToken = null,
    ): Cart;

    /**
     * Merge guest cart into user cart after authentication.
     */
    public function mergeGuestCart(User $user, string $guestToken): Cart;
}
