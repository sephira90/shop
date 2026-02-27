<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Commands;

use App\Application\Admin\Products\Dto\AdminProductResultDto;
use App\Services\Admin\AdminCatalogService;

final class UpdateAdminProductHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminCatalogService $adminCatalogService,
    ) {}

    /**
     * Execute admin product update command.
     */
    public function handle(UpdateAdminProductCommand $command): AdminProductResultDto
    {
        $product = $this->adminCatalogService->updateProduct($command->product, $command->input);

        return AdminProductResultDto::fromProduct($product);
    }
}
