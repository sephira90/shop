<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Queries;

use App\Application\Account\Orders\Dto\AccountOrderListFilterDto;
use App\Models\User;

final readonly class PaginateLegacyAccountOrdersQuery
{
    public function __construct(
        public User $user,
        public AccountOrderListFilterDto $filter,
    ) {}
}
