<?php

declare(strict_types=1);

namespace App\Domains\Cart\Application\Commands;

use App\Domains\Cart\Application\Dto\CartResultDto;
use App\Domains\Cart\Contracts\CartServiceInterface;

final class UpsertCartItemHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly CartServiceInterface $cartService,
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
            $command->user,
            $command->input->guestToken,
        );

        return $this->cartService->toResultDto($cart);
    }
}
