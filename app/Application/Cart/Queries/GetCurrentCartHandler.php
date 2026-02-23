<?php

declare(strict_types=1);

namespace App\Application\Cart\Queries;

use App\Services\Cart\CartService;

final class GetCurrentCartHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    /**
     * Execute current cart show query.
     *
     * @return array<string, mixed>
     */
    public function handle(GetCurrentCartQuery $query): array
    {
        $cart = $this->cartService->resolve($query->user, $query->guestToken);

        return $this->cartService->payload($cart);
    }
}
