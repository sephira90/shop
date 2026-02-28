<?php

declare(strict_types=1);

namespace App\Support\Observability\Dto;

final readonly class ObservabilityReportRunResultDto
{
    /**
     * @param  array{
     *     minutes:int,
     *     source:string,
     *     api:array{count:int,avg_duration_ms:float,slow_count:int},
     *     catalog:list<array{
     *         segment:string,
     *         count:int,
     *         hit_count:int,
     *         miss_count:int,
     *         hit_ratio:float,
     *         avg_duration_ms:float,
     *         slow_miss_count:int
     *     }>,
     *     webhook:list<array{
     *         provider:string,
     *         count:int,
     *         processed_count:int,
     *         duplicate_count:int,
     *         rejected_count:int,
     *         avg_duration_ms:float,
     *         avg_lag_ms:?float,
     *         lag_warn_count:int
     *     }>
     * }  $snapshot
     * @param  list<string>  $warnings
     * @param  list<string>  $violations
     */
    public function __construct(
        public ObservabilityReportOptionsDto $options,
        public array $snapshot,
        public array $warnings,
        public array $violations,
    ) {}

    public function passed(): bool
    {
        return $this->violations === [];
    }
}
