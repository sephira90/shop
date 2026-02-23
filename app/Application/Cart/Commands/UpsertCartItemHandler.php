<?php

declare(strict_types=1);

namespace App\Application\Cart\Commands;

use App\Services\Cart\CartService;

final class UpsertCartItemHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    /**
     * Execute cart item upsert command.
     *
     * @return array<string, mixed>
     */
    public function handle(UpsertCartItemCommand $command): array
    {
        $cart = $this->cartService->resolve($command->user, $command->guestToken);
        $cart = $this->cartService->upsertItem($cart, $command->variantId, $command->quantity);

        return $this->cartService->payload($cart);
    }
}
