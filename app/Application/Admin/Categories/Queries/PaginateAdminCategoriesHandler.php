<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Queries;

use App\Application\Admin\Categories\Contracts\AdminCategoryReadRepository;
use App\Application\Admin\Categories\Dto\AdminCategoryPaginatedResultDto;

final class PaginateAdminCategoriesHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly AdminCategoryReadRepository $categoryRepository,
    ) {}

    /**
     * Execute admin categories list query.
     */
    public function handle(PaginateAdminCategoriesQuery $query): AdminCategoryPaginatedResultDto
    {
        $paginator = $this->categoryRepository->paginateForAdmin($query->filter);

        return AdminCategoryPaginatedResultDto::fromPaginator($paginator);
    }
}
