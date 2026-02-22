<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Queries;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginateAdminCategoriesHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    /**
     * Execute admin categories list query.
     *
     * @return LengthAwarePaginator<int, Category>
     */
    public function handle(PaginateAdminCategoriesQuery $query): LengthAwarePaginator
    {
        return $this->categoryRepository->paginateForAdmin($query->filter);
    }
}
