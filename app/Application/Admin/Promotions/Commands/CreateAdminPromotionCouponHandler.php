<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Models\Coupon;
use App\Services\Admin\AdminPromotionService;

final class CreateAdminPromotionCouponHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminPromotionService $adminPromotionService,
    ) {}

    /**
     * Execute admin promotion coupon create command.
     */
    public function handle(CreateAdminPromotionCouponCommand $command): Coupon
    {
        return $this->adminPromotionService->createCoupon($command->promotion, $command->input);
    }
}
