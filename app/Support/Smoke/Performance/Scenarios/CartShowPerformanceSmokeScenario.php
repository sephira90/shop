<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Scenarios;

use App\Services\Cart\CartService;
use App\Support\Smoke\Performance\Contracts\PerformanceSmokeScenario;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeContextDto;

final class CartShowPerformanceSmokeScenario implements PerformanceSmokeScenario
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function name(): string
    {
        return 'cart_show';
    }

    public function usesRollback(): bool
    {
        return false;
    }

    public function run(PerformanceSmokeContextDto $context): void
    {
        $cart = $this->cartService->resolve(null, $context->cartGuestToken);
        $this->cartService->toResultDto($cart);
    }
}
