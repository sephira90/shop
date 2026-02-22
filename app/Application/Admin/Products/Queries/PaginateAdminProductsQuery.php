<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Queries;

use App\Filters\Admin\AdminProductListFilter;

final readonly class PaginateAdminProductsQuery
{
    /**
     * Create query payload for admin products pagination.
     */
    public function __construct(
        public AdminProductListFilter $filter,
    ) {}
}
