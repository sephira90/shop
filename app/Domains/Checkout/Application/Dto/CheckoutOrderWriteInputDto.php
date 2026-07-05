<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Application\Dto;

use App\Domain\ValueObjects\Money;
use App\Models\Cart;
use App\Models\User;

final readonly class CheckoutOrderWriteInputDto
{
    public function __construct(
        public Cart $cart,
        public CheckoutPlaceOrderInputDto $checkoutInput,
        public ?User $user,
        public CheckoutCartPreparationDto $cartPreparation,
        public Money $discountTotal,
        public Money $shippingTotal,
    ) {}
}
