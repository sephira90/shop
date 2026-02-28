<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Dto;

final readonly class PerformanceSmokeMeasurementDto
{
    public function __construct(
        public string $name,
        public float $durationMs,
        public int $queries,
    ) {}
}
