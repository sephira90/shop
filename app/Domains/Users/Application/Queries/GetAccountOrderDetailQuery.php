<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Queries;

use App\Models\User;

final readonly class GetAccountOrderDetailQuery
{
    public function __construct(
        public User $user,
        public string $orderId,
    ) {}
}
