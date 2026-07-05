<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Queries;

use App\Domains\Users\Application\Dto\AccountOrderListFilterDto;
use App\Models\User;

final readonly class PaginateAccountOrdersQuery
{
    public function __construct(
        public User $user,
        public AccountOrderListFilterDto $filter,
    ) {}
}
