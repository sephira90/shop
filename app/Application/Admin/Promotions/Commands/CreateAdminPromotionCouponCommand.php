<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Application\Admin\Promotions\Dto\CreateAdminPromotionCouponInputDto;
use App\Models\Promotion;

final readonly class CreateAdminPromotionCouponCommand
{
    public function __construct(
        public Promotion $promotion,
        public CreateAdminPromotionCouponInputDto $input,
    ) {}
}
