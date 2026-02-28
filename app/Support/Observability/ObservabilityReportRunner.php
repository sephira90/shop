<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Observability\Dto\ObservabilityReportOptionsDto;
use App\Support\Observability\Dto\ObservabilityReportRunResultDto;

final class ObservabilityReportRunner
{
    public function __construct(
        private readonly ObservabilityService $observabilityService,
        private readonly ObservabilityReportThresholdEvaluator $thresholdEvaluator,
    ) {}

    public function run(ObservabilityReportOptionsDto $options): ObservabilityReportRunResultDto
    {
        $warnings = [];

        if (! config('observability.enabled', true)) {
            $warnings[] = 'Observability hooks are disabled (OBSERVABILITY_ENABLED=false). Snapshot may be empty.';
        }

        $snapshot = $this->observabilityService->snapshot($options->minutes, $options->source);
        $evaluation = $this->thresholdEvaluator->evaluate($snapshot, $options);

        return new ObservabilityReportRunResultDto(
            options: $options,
            snapshot: $snapshot,
            warnings: [...$warnings, ...$evaluation->warnings],
            violations: $evaluation->violations,
        );
    }
}
