<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Models\Coupon;
use App\Services\Admin\AdminPromotionService;

final class UpdateAdminPromotionCouponHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminPromotionService $adminPromotionService,
    ) {}

    /**
     * Execute admin promotion coupon update command.
     */
    public function handle(UpdateAdminPromotionCouponCommand $command): Coupon
    {
        return $this->adminPromotionService->updateCoupon($command->coupon, $command->input);
    }
}
