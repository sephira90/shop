<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Scenarios;

use App\Domains\Cart\Contracts\CartServiceInterface;
use App\Domains\Checkout\Contracts\CheckoutServiceInterface;
use App\Support\Smoke\Performance\Contracts\PerformanceSmokeScenario;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeContextDto;
use Illuminate\Support\Str;

final class CheckoutPlaceOrderPerformanceSmokeScenario implements PerformanceSmokeScenario
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
        private readonly CheckoutServiceInterface $checkoutService,
    ) {}

    public function name(): string
    {
        return 'checkout_place_order';
    }

    public function usesRollback(): bool
    {
        return true;
    }

    public function run(PerformanceSmokeContextDto $context): void
    {
        $cart = $this->cartService->resolveForCheckout(null, $context->checkoutGuestToken);
        $idempotencyKey = 'perf-checkout-'.Str::lower(Str::random(16));
        $this->checkoutService->placeOrder($cart, $context->checkoutPayload, $idempotencyKey);
    }
}
