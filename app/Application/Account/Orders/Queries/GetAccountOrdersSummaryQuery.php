<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Queries;

use App\Models\User;

final readonly class GetAccountOrdersSummaryQuery
{
    public function __construct(
        public User $user,
    ) {}
}
