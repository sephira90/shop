<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\Dto\ResetAuthPasswordInputDto;

final readonly class ResetAuthPasswordCommand
{
    /**
     * Create reset-password command.
     */
    public function __construct(
        public ResetAuthPasswordInputDto $input,
    ) {}
}
