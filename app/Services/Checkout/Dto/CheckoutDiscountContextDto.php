<?php

declare(strict_types=1);

namespace App\Services\Checkout\Dto;

use App\Models\Coupon;
use App\Models\Promotion;

final readonly class CheckoutDiscountContextDto
{
    public function __construct(
        public float $discountTotal,
        public ?Coupon $coupon,
        public ?Promotion $promotion,
    ) {}
}
