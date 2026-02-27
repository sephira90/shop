<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Queries;

use App\Application\Admin\Orders\Dto\AdminOrderListFilterDto;

final readonly class PaginateAdminOrdersQuery
{
    /**
     * Create query payload for admin orders pagination.
     */
    public function __construct(
        public AdminOrderListFilterDto $filter,
    ) {}
}
