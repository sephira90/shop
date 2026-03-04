<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto;
use App\Domain\ValueObjects\Money;
use App\Models\Cart;

final readonly class FreeCheckoutShippingCostResolver implements CheckoutShippingCostResolver
{
    public function resolve(Cart $cart, CheckoutPlaceOrderInputDto $checkoutInput): Money
    {
        return Money::zero($checkoutInput->currency);
    }
}
