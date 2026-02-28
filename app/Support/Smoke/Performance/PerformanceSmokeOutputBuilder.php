<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance;

use App\Support\Smoke\Dto\SmokeCommandOutputDto;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeMeasurementDto;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeRunResultDto;
use App\Support\Smoke\SmokeCommandOutputFactory;

final class PerformanceSmokeOutputBuilder
{
    public function __construct(
        private readonly SmokeCommandOutputFactory $outputFactory,
    ) {}

    public function build(PerformanceSmokeRunResultDto $result): SmokeCommandOutputDto
    {
        return $this->outputFactory->build(
            headers: ['check', 'duration_ms', 'queries'],
            rows: array_map(
                static fn (PerformanceSmokeMeasurementDto $measurement): array => [
                    $measurement->name,
                    number_format($measurement->durationMs, 2, '.', ''),
                    (string) $measurement->queries,
                ],
                $result->measurements,
            ),
            successMessage: 'Performance smoke checks passed.',
            rolledBack: $result->rolledBack,
        );
    }
}
