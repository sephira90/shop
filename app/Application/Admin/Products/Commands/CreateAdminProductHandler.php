<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Commands;

use App\Models\Product;
use App\Services\Admin\AdminCatalogService;

final class CreateAdminProductHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminCatalogService $adminCatalogService,
    ) {}

    /**
     * Execute admin product create command.
     */
    public function handle(CreateAdminProductCommand $command): Product
    {
        return $this->adminCatalogService->createProduct($command->payload);
    }
}
