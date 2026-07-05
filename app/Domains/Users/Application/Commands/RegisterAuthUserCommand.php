<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Commands;

use App\Domains\Users\Application\Dto\RegisterAuthInputDto;

final readonly class RegisterAuthUserCommand
{
    /**
     * Create command payload for auth register flow.
     */
    public function __construct(
        public RegisterAuthInputDto $input,
    ) {}
}
