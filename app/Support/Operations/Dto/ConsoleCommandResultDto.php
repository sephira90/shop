<?php

declare(strict_types=1);

namespace App\Support\Operations\Dto;

final readonly class ConsoleCommandResultDto
{
    public function __construct(
        public string $command,
        public int $exitCode,
        public string $output,
    ) {}

    public function succeeded(): bool
    {
        return $this->exitCode === 0;
    }
}
