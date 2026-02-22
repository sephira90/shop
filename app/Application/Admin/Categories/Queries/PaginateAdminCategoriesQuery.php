<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Queries;

use App\Filters\Admin\AdminCategoryListFilter;

final readonly class PaginateAdminCategoriesQuery
{
    /**
     * Create query payload for admin categories pagination.
     */
    public function __construct(
        public AdminCategoryListFilter $filter,
    ) {}
}
