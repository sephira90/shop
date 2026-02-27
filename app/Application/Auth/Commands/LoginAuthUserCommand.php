<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\Dto\LoginAuthInputDto;

final readonly class LoginAuthUserCommand
{
    /**
     * Create command payload for auth login flow.
     */
    public function __construct(
        public LoginAuthInputDto $input,
    ) {}
}
