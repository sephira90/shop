<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Commands;

use App\Domains\Users\Application\Dto\ResetAuthPasswordInputDto;

final readonly class ResetAuthPasswordCommand
{
    /**
     * Create reset-password command.
     */
    public function __construct(
        public ResetAuthPasswordInputDto $input,
    ) {}
}
