<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Queries;

use App\Application\Admin\Products\Dto\AdminProductPaginatedResultDto;
use App\Repositories\ProductRepository;

final class PaginateAdminProductsHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    /**
     * Execute admin products list query.
     */
    public function handle(PaginateAdminProductsQuery $query): AdminProductPaginatedResultDto
    {
        $paginator = $this->productRepository->paginateForAdmin($query->filter);

        return AdminProductPaginatedResultDto::fromPaginator($paginator);
    }
}
