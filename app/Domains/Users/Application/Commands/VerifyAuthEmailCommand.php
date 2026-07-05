<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Commands;

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
