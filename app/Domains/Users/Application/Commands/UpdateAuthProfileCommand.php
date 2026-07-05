<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Commands;

use App\Domains\Users\Application\Dto\UpdateAuthProfileInputDto;
use App\Models\User;

final readonly class UpdateAuthProfileCommand
{
    /**
     * Create command payload for auth profile update flow.
     */
    public function __construct(
        public User $user,
        public UpdateAuthProfileInputDto $input,
    ) {}
}
