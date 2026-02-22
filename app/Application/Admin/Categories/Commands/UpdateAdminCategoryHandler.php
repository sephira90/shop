<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Commands;

use App\Models\Category;
use App\Services\Admin\AdminCategoryService;

final class UpdateAdminCategoryHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminCategoryService $adminCategoryService,
    ) {}

    /**
     * Execute admin category update command.
     */
    public function handle(UpdateAdminCategoryCommand $command): Category
    {
        return $this->adminCategoryService->update($command->category, $command->payload);
    }
}
