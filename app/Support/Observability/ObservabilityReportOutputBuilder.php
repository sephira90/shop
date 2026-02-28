<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Observability\Dto\ObservabilityReportOutputDto;
use App\Support\Observability\Dto\ObservabilityReportRunResultDto;
use RuntimeException;

final class ObservabilityReportOutputBuilder
{
    public function build(ObservabilityReportRunResultDto $result): ObservabilityReportOutputDto
    {
        if ($result->options->json) {
            $encoded = json_encode($result->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if (! is_string($encoded)) {
                throw new RuntimeException('Unable to encode observability snapshot.');
            }

            return new ObservabilityReportOutputDto(
                jsonOutput: $encoded,
                summaryHeaders: [],
                summaryRows: [],
                catalogHeaders: [],
                catalogRows: [],
                catalogEmptyMessage: null,
                webhookHeaders: [],
                webhookRows: [],
                webhookEmptyMessage: null,
            );
        }

        return new ObservabilityReportOutputDto(
            jsonOutput: null,
            summaryHeaders: ['metric', 'value'],
            summaryRows: [
                ['window_minutes', (string) $result->snapshot['minutes']],
                ['snapshot_source', (string) $result->snapshot['source']],
                ['api_request_count', (string) $result->snapshot['api']['count']],
                ['api_avg_duration_ms', $this->formatFloat((float) $result->snapshot['api']['avg_duration_ms'])],
                ['api_slow_count', (string) $result->snapshot['api']['slow_count']],
            ],
            catalogHeaders: ['segment', 'count', 'hit', 'miss', 'hit_ratio', 'avg_ms', 'slow_miss'],
            catalogRows: array_map(fn (array $row): array => [
                $row['segment'],
                (string) $row['count'],
                (string) $row['hit_count'],
                (string) $row['miss_count'],
                $this->formatFloat((float) $row['hit_ratio'], 4),
                $this->formatFloat((float) $row['avg_duration_ms']),
                (string) $row['slow_miss_count'],
            ], $result->snapshot['catalog']),
            catalogEmptyMessage: $result->snapshot['catalog'] === [] ? 'Catalog metrics: no samples in selected window.' : null,
            webhookHeaders: ['provider', 'count', 'processed', 'duplicate', 'rejected', 'avg_ms', 'avg_lag_ms', 'lag_warn'],
            webhookRows: array_map(fn (array $row): array => [
                $row['provider'],
                (string) $row['count'],
                (string) $row['processed_count'],
                (string) $row['duplicate_count'],
                (string) $row['rejected_count'],
                $this->formatFloat((float) $row['avg_duration_ms']),
                $row['avg_lag_ms'] === null ? '-' : $this->formatFloat((float) $row['avg_lag_ms']),
                (string) $row['lag_warn_count'],
            ], $result->snapshot['webhook']),
            webhookEmptyMessage: $result->snapshot['webhook'] === [] ? 'Webhook metrics: no samples in selected window.' : null,
        );
    }

    private function formatFloat(float $value, int $precision = 2): string
    {
        return number_format($value, $precision, '.', '');
    }
}
