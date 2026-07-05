<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Commands;

use App\Domains\Users\Application\Dto\ForgotAuthPasswordInputDto;

final readonly class ForgotAuthPasswordCommand
{
    /**
     * Create forgot-password command.
     */
    public function __construct(
        public ForgotAuthPasswordInputDto $input,
    ) {}
}
