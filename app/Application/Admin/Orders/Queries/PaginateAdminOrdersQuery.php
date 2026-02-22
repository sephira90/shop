<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Queries;

use App\Filters\Admin\AdminOrderListFilter;

final readonly class PaginateAdminOrdersQuery
{
    /**
     * Create query payload for admin orders pagination.
     */
    public function __construct(
        public AdminOrderListFilter $filter,
    ) {}
}
