<?php

declare(strict_types=1);

namespace App\Application\Cart\Commands;

use App\Application\Cart\Dto\CartResultDto;
use App\Contracts\CartServiceInterface;

final class RemoveCartItemHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly CartServiceInterface $cartService,
    ) {}

    /**
     * Execute cart item remove command.
     */
    public function handle(RemoveCartItemCommand $command): CartResultDto
    {
        $cart = $this->cartService->resolve($command->user, $command->input->guestToken);
        $cart = $this->cartService->removeItem(
            $cart,
            $command->input->variantId,
            $command->user,
            $command->input->guestToken,
        );

        return $this->cartService->toResultDto($cart);
    }
}
