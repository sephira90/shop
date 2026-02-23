<?php

declare(strict_types=1);

namespace App\Support\Smoke;

final readonly class SmokeCheckResult
{
    /**
     * Create smoke check result value object.
     */
    public function __construct(
        public string $name,
        public int $status,
        public string $result = 'ok',
    ) {}

    /**
     * Format result for console table output.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public function toTableRow(): array
    {
        return [
            $this->name,
            (string) $this->status,
            $this->result,
        ];
    }
}
