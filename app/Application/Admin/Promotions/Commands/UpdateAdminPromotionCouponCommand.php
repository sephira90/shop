<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Application\Admin\Promotions\Dto\UpdateAdminPromotionCouponInputDto;
use App\Models\Coupon;

final readonly class UpdateAdminPromotionCouponCommand
{
    public function __construct(
        public Coupon $coupon,
        public UpdateAdminPromotionCouponInputDto $input,
    ) {}
}
