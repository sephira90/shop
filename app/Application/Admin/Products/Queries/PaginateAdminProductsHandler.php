<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Queries;

use App\Application\Admin\Products\Dto\AdminProductPaginatedResultDto;
use App\Repositories\AdminProductReadRepository;

final class PaginateAdminProductsHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly AdminProductReadRepository $adminProductReadRepository,
    ) {}

    /**
     * Execute admin products list query.
     */
    public function handle(PaginateAdminProductsQuery $query): AdminProductPaginatedResultDto
    {
        $paginator = $this->adminProductReadRepository->paginateForAdmin($query->filter);

        return AdminProductPaginatedResultDto::fromPaginator($paginator);
    }
}
