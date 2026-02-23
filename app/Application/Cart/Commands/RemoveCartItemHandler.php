<?php

declare(strict_types=1);

namespace App\Application\Cart\Commands;

use App\Services\Cart\CartService;

final class RemoveCartItemHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    /**
     * Execute cart item remove command.
     *
     * @return array<string, mixed>
     */
    public function handle(RemoveCartItemCommand $command): array
    {
        $cart = $this->cartService->resolve($command->user, $command->guestToken);
        $cart = $this->cartService->removeItem($cart, $command->variantId);

        return $this->cartService->payload($cart);
    }
}
