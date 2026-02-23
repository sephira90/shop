<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Models\User;

final readonly class UpdateAuthProfileCommand
{
    /**
     * Create command payload for auth profile update flow.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public User $user,
        public array $payload,
    ) {}
}
