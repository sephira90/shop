<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Services\Dto;

use App\Domain\ValueObjects\Money;
use App\Models\Coupon;
use App\Models\Promotion;

final readonly class CheckoutDiscountContextDto
{
    public function __construct(
        public Money $discountTotal,
        public ?Coupon $coupon,
        public ?Promotion $promotion,
    ) {}
}
