<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Dto;

final readonly class PerformanceSmokeBudgetDto
{
    public function __construct(
        public string $scenario,
        public float $maxMs,
        public int $maxQueries,
    ) {}
}
