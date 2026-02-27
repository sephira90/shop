<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Commands;

use App\Application\Admin\Products\Dto\AdminProductResultDto;
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
    public function handle(CreateAdminProductCommand $command): AdminProductResultDto
    {
        $product = $this->adminCatalogService->createProduct($command->input);

        return AdminProductResultDto::fromProduct($product);
    }
}
