<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Models\User;

final readonly class LogoutAuthUserCommand
{
    /**
     * Create command payload for auth logout flow.
     */
    public function __construct(
        public User $user,
    ) {}
}
