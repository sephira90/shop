<?php

declare(strict_types=1);

namespace App\Application\Checkout\Queries;

use App\Models\User;

final readonly class GetMyOrdersSummaryQuery
{
    /**
     * Create query payload for account order summary.
     */
    public function __construct(
        public User $user,
    ) {}
}
