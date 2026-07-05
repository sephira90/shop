<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Services;

use App\Domain\ValueObjects\Money;
use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderInputDto;
use App\Domains\Checkout\Contracts\CheckoutShippingCostResolver;
use App\Models\Cart;

final readonly class FreeCheckoutShippingCostResolver implements CheckoutShippingCostResolver
{
    public function resolve(Cart $cart, CheckoutPlaceOrderInputDto $checkoutInput): Money
    {
        return Money::zero($checkoutInput->currency);
    }
}
