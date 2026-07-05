<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Commands;

use App\Models\User;

final readonly class ResendAuthVerificationCommand
{
    /**
     * Create resend-verification command.
     */
    public function __construct(
        public User $user,
    ) {}
}
