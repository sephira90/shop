<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Commands;

use App\Application\Admin\Categories\Dto\AdminCategoryResultDto;
use App\Services\Admin\AdminCategoryService;

final class CreateAdminCategoryHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminCategoryService $adminCategoryService,
    ) {}

    /**
     * Execute admin category create command.
     */
    public function handle(CreateAdminCategoryCommand $command): AdminCategoryResultDto
    {
        $category = $this->adminCategoryService->create($command->input);

        return AdminCategoryResultDto::fromCategory($category);
    }
}
