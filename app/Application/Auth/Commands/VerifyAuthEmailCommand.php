<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

final readonly class VerifyAuthEmailCommand
{
    /**
     * Create verify-email command.
     */
    public function __construct(
        public int $userId,
        public string $hash,
    ) {}
}
