<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto;
use App\Domain\ValueObjects\Money;
use App\Models\Cart;

interface CheckoutShippingCostResolver
{
    public function resolve(Cart $cart, CheckoutPlaceOrderInputDto $checkoutInput): Money;
}
