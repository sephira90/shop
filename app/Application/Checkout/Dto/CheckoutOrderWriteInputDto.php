<?php

declare(strict_types=1);

namespace App\Application\Checkout\Dto;

use App\Models\Cart;
use App\Models\User;

final readonly class CheckoutOrderWriteInputDto
{
    public function __construct(
        public Cart $cart,
        public CheckoutPlaceOrderInputDto $checkoutInput,
        public ?User $user,
        public CheckoutCartPreparationDto $cartPreparation,
        public float $discountTotal,
        public float $shippingTotal,
    ) {}
}
