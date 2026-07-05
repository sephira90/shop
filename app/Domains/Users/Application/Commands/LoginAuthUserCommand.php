<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Commands;

use App\Domains\Users\Application\Dto\LoginAuthInputDto;

final readonly class LoginAuthUserCommand
{
    /**
     * Create command payload for auth login flow.
     */
    public function __construct(
        public LoginAuthInputDto $input,
    ) {}
}
