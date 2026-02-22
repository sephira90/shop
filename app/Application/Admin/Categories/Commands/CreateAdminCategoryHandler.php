<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Commands;

use App\Models\Category;
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
    public function handle(CreateAdminCategoryCommand $command): Category
    {
        return $this->adminCategoryService->create($command->payload);
    }
}
