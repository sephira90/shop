<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Application\Cart\Dto\CartResultDto;
use App\Contracts\CartMutationServiceInterface;
use App\Contracts\CartServiceInterface;
use App\Models\Cart;
use App\Models\User;

final class CartService implements CartServiceInterface
{
    /**
     * Create service instance.
     */
    public function __construct(
        private readonly CartResolver $cartResolver,
        private readonly CartMutationServiceInterface $cartMutationService,
        private readonly CartResultMapper $cartResultMapper,
    ) {}

    /**
     * Resolve active cart for user or guest token.
     */
    public function resolve(?User $user, ?string $guestToken = null): Cart
    {
        return $this->cartResolver->resolve($user, $guestToken);
    }

    /**
     * Resolve latest cart for checkout retries.
     */
    public function resolveForCheckout(?User $user, ?string $guestToken = null): Cart
    {
        return $this->cartResolver->resolveForCheckout($user, $guestToken);
    }

    /**
     * Add or update cart item.
     */
    public function upsertItem(
        Cart $cart,
        int $variantId,
        int $quantity,
        ?User $user = null,
        ?string $guestToken = null,
    ): Cart {
        return $this->cartMutationService->upsertItem($cart, $variantId, $quantity, $user, $guestToken);
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(
        Cart $cart,
        int $variantId,
        ?User $user = null,
        ?string $guestToken = null,
    ): Cart {
        return $this->cartMutationService->removeItem($cart, $variantId, $user, $guestToken);
    }

    /**
     * Merge guest cart into user cart after authentication.
     */
    public function mergeGuestCart(User $user, string $guestToken): Cart
    {
        return $this->cartMutationService->mergeGuestCart($user, $guestToken);
    }

    /**
     * Build normalized cart result DTO.
     */
    public function toResultDto(Cart $cart): CartResultDto
    {
        return $this->cartResultMapper->toResultDto($cart);
    }
}
