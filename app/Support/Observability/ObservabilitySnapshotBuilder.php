<?php

declare(strict_types=1);

namespace App\Support\Observability;

final readonly class ObservabilitySnapshotBuilder
{
    public function __construct(
        private ObservabilityMetricStore $observabilityMetricStore,
    ) {}

    /**
     * @return array{
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
     * }
     */
    public function build(int $minutes, string $source): array
    {
        $window = max(1, min(1440, $minutes));
        $api = $this->observabilityMetricStore->apiMetrics($window, $source);
        $catalog = $this->observabilityMetricStore->catalogMetrics($window, $source);
        $webhook = $this->observabilityMetricStore->webhookMetrics($window, $source);

        return [
            'minutes' => $window,
            'source' => $source,
            'api' => [
                'count' => $api['count'],
                'avg_duration_ms' => $api['count'] > 0 ? round($api['duration_ms_total'] / $api['count'], 2) : 0.0,
                'slow_count' => $api['slow_count'],
            ],
            'catalog' => array_map(
                static fn (array $row): array => [
                    'segment' => $row['segment'],
                    'count' => $row['count'],
                    'hit_count' => $row['hit_count'],
                    'miss_count' => $row['miss_count'],
                    'hit_ratio' => round($row['hit_count'] / $row['count'], 4),
                    'avg_duration_ms' => round($row['duration_ms_total'] / $row['count'], 2),
                    'slow_miss_count' => $row['slow_miss_count'],
                ],
                $catalog,
            ),
            'webhook' => array_map(
                static fn (array $row): array => [
                    'provider' => $row['provider'],
                    'count' => $row['count'],
                    'processed_count' => $row['processed_count'],
                    'duplicate_count' => $row['duplicate_count'],
                    'rejected_count' => $row['rejected_count'],
                    'avg_duration_ms' => round($row['duration_ms_total'] / $row['count'], 2),
                    'avg_lag_ms' => $row['lag_samples'] > 0 ? round($row['lag_ms_total'] / $row['lag_samples'], 2) : null,
                    'lag_warn_count' => $row['lag_warn_count'],
                ],
                $webhook,
            ),
        ];
    }
}
