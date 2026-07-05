<?php

declare(strict_types=1);

namespace App\Domains\Cart\Application\Queries;

use App\Domains\Cart\Application\Dto\CartResultDto;
use App\Domains\Cart\Contracts\CartServiceInterface;

final class GetCurrentCartHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly CartServiceInterface $cartService,
    ) {}

    /**
     * Execute current cart show query.
     */
    public function handle(GetCurrentCartQuery $query): CartResultDto
    {
        $cart = $this->cartService->resolve($query->user, $query->guestToken);

        return $this->cartService->toResultDto($cart);
    }
}
