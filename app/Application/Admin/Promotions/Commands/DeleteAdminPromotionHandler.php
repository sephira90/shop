<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Services\Admin\AdminPromotionService;

final class DeleteAdminPromotionHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminPromotionService $adminPromotionService,
    ) {}

    /**
     * Execute admin promotion delete command.
     */
    public function handle(DeleteAdminPromotionCommand $command): void
    {
        $this->adminPromotionService->delete($command->promotion);
    }
}
