<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Commands;

use App\Services\Admin\AdminCatalogService;

final class DeleteAdminProductHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminCatalogService $adminCatalogService,
    ) {}

    /**
     * Execute admin product delete command.
     */
    public function handle(DeleteAdminProductCommand $command): void
    {
        $this->adminCatalogService->deleteProduct($command->product);
    }
}
