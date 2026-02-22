<?php

declare(strict_types=1);

namespace App\Application\Checkout\Queries;

use App\Filters\Account\AccountOrderListFilter;
use App\Models\User;

final readonly class PaginateMyOrdersQuery
{
    /**
     * Create query payload for account order list.
     */
    public function __construct(
        public User $user,
        public AccountOrderListFilter $filter,
    ) {}
}
