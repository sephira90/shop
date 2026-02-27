<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\Dto\ForgotAuthPasswordInputDto;

final readonly class ForgotAuthPasswordCommand
{
    /**
     * Create forgot-password command.
     */
    public function __construct(
        public ForgotAuthPasswordInputDto $input,
    ) {}
}
