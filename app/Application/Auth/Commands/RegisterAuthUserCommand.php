<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\Dto\RegisterAuthInputDto;

final readonly class RegisterAuthUserCommand
{
    /**
     * Create command payload for auth register flow.
     */
    public function __construct(
        public RegisterAuthInputDto $input,
    ) {}
}
