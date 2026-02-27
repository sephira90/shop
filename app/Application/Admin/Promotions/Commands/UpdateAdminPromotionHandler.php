<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Models\Promotion;
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
    public function handle(UpdateAdminPromotionCommand $command): Promotion
    {
        return $this->adminPromotionService->update($command->promotion, $command->input);
    }
}
