<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Commands;

use App\Application\Admin\Categories\Dto\AdminCategoryResultDto;
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
    public function handle(UpdateAdminCategoryCommand $command): AdminCategoryResultDto
    {
        $category = $this->adminCategoryService->update($command->category, $command->input);

        return AdminCategoryResultDto::fromCategory($category);
    }
}
