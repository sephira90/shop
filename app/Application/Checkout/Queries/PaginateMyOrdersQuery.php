<?php

declare(strict_types=1);

namespace App\Application\Checkout\Queries;

use App\Application\Checkout\Dto\AccountOrderListFilterDto;
use App\Models\User;

final readonly class PaginateMyOrdersQuery
{
    /**
     * Create query payload for account order list.
     */
    public function __construct(
        public User $user,
        public AccountOrderListFilterDto $filter,
    ) {}
}
