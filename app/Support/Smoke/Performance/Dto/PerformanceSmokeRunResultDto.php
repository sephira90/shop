<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Dto;

final readonly class PerformanceSmokeRunResultDto
{
    /**
     * @param  list<PerformanceSmokeMeasurementDto>  $measurements
     * @param  list<string>  $violations
     */
    public function __construct(
        public array $measurements,
        public array $violations,
        public bool $rolledBack,
    ) {}

    public function passed(): bool
    {
        return $this->violations === [];
    }
}
