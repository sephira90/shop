<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Application\Cart\Dto\CartResultDto;
use App\Models\Cart;
use App\Models\User;

interface CartServiceInterface
{
    /**
     * Resolve active cart for user or guest token.
     */
    public function resolve(?User $user, ?string $guestToken = null): Cart;

    /**
     * Resolve latest cart for checkout retries.
     */
    public function resolveForCheckout(?User $user, ?string $guestToken = null): Cart;

    /**
     * Add or update cart item.
     */
    public function upsertItem(Cart $cart, int $variantId, int $quantity): Cart;

    /**
     * Remove item from cart.
     */
    public function removeItem(Cart $cart, int $variantId): Cart;

    /**
     * Merge guest cart into user cart after authentication.
     */
    public function mergeGuestCart(User $user, string $guestToken): Cart;

    /**
     * Build normalized cart result DTO.
     */
    public function toResultDto(Cart $cart): CartResultDto;
}
