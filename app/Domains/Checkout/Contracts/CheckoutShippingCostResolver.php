<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Contracts;

use App\Domain\ValueObjects\Money;
use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderInputDto;
use App\Models\Cart;

interface CheckoutShippingCostResolver
{
    public function resolve(Cart $cart, CheckoutPlaceOrderInputDto $checkoutInput): Money;
}
