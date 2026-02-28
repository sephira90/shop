<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Application\Admin\Promotions\Dto\AdminPromotionCouponResultDto;
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
    public function handle(CreateAdminPromotionCouponCommand $command): AdminPromotionCouponResultDto
    {
        $coupon = $this->adminPromotionService->createCoupon($command->promotion, $command->input);

        return AdminPromotionCouponResultDto::fromCoupon($coupon);
    }
}
