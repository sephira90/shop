<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Models\Promotion;
use App\Services\Admin\AdminPromotionService;

final class CreateAdminPromotionHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminPromotionService $adminPromotionService,
    ) {}

    /**
     * Execute admin promotion create command.
     */
    public function handle(CreateAdminPromotionCommand $command): Promotion
    {
        return $this->adminPromotionService->create($command->input);
    }
}
