<?php

declare(strict_types=1);

namespace App\Application\Cart\Commands;

use App\Application\Cart\Dto\CartResultDto;
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
     */
    public function handle(UpsertCartItemCommand $command): CartResultDto
    {
        $cart = $this->cartService->resolve($command->user, $command->input->guestToken);
        $cart = $this->cartService->upsertItem(
            $cart,
            $command->input->productVariantId,
            $command->input->quantity,
        );

        return $this->cartService->toResultDto($cart);
    }
}
