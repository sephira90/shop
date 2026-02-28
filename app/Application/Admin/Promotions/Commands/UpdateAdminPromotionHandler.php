<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Application\Admin\Promotions\Dto\AdminPromotionResultDto;
use App\Services\Admin\AdminPromotionService;

final class UpdateAdminPromotionHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminPromotionService $adminPromotionService,
    ) {}

    /**
     * Execute admin promotion update command.
     */
    public function handle(UpdateAdminPromotionCommand $command): AdminPromotionResultDto
    {
        $promotion = $this->adminPromotionService->update($command->promotion, $command->input);

        return AdminPromotionResultDto::fromPromotion($promotion);
    }
}
