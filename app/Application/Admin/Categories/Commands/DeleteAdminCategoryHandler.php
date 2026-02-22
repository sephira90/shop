<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Commands;

use App\Services\Admin\AdminCategoryService;

final class DeleteAdminCategoryHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminCategoryService $adminCategoryService,
    ) {}

    /**
     * Execute admin category delete command.
     */
    public function handle(DeleteAdminCategoryCommand $command): void
    {
        $this->adminCategoryService->delete($command->category);
    }
}
