<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Queries;

use App\Models\User;

final readonly class GetAuthProfileQuery
{
    /**
     * Create query payload for auth me profile flow.
     */
    public function __construct(
        public User $user,
    ) {}
}
