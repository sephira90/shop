<?php

declare(strict_types=1);

namespace App\Support\Observability\Dto;

final readonly class ObservabilityReportEvaluationResultDto
{
    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $violations
     */
    public function __construct(
        public array $warnings,
        public array $violations,
    ) {}
}
