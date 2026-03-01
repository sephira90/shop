<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Queries;

use App\Application\Admin\Products\Dto\AdminProductResultDto;
use App\Repositories\AdminProductReadRepository;

final class GetAdminProductDetailHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly AdminProductReadRepository $adminProductReadRepository,
    ) {}

    /**
     * Execute admin product detail query.
     */
    public function handle(GetAdminProductDetailQuery $query): AdminProductResultDto
    {
        $product = $this->adminProductReadRepository->loadForAdmin($query->product);

        return AdminProductResultDto::fromProduct($product);
    }
}
