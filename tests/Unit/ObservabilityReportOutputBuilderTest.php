<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Observability\Dto\ObservabilityReportOptionsDto;
use App\Support\Observability\Dto\ObservabilityReportRunResultDto;
use App\Support\Observability\ObservabilityReportOutputBuilder;
use Tests\TestCase;

class ObservabilityReportOutputBuilderTest extends TestCase
{
    public function test_build_formats_console_tables_for_non_json_output(): void
    {
        $builder = new ObservabilityReportOutputBuilder;

        $output = $builder->build(new ObservabilityReportRunResultDto(
            options: new ObservabilityReportOptionsDto(
                minutes: 60,
                source: 'runtime',
                maxApiSlowRate: null,
                maxWebhookLagWarnRate: null,
                requireApiSamples: false,
                requireWebhookSamples: false,
                json: false,
            ),
            snapshot: [
                'minutes' => 60,
                'source' => 'runtime',
                'api' => [
                    'count' => 2,
                    'avg_duration_ms' => 12.345,
                    'slow_count' => 1,
                ],
                'catalog' => [[
                    'segment' => 'products_list',
                    'count' => 2,
                    'hit_count' => 1,
                    'miss_count' => 1,
                    'hit_ratio' => 0.5,
                    'avg_duration_ms' => 11.11,
                    'slow_miss_count' => 0,
                ]],
                'webhook' => [[
                    'provider' => 'payment',
                    'count' => 2,
                    'processed_count' => 2,
                    'duplicate_count' => 0,
                    'rejected_count' => 0,
                    'avg_duration_ms' => 31.234,
                    'avg_lag_ms' => 201.5,
                    'lag_warn_count' => 1,
                ]],
            ],
            warnings: [],
            violations: [],
        ));

        $this->assertNull($output->jsonOutput);
        $this->assertSame(['metric', 'value'], $output->summaryHeaders);
        $this->assertSame('12.35', $output->summaryRows[3][1]);
        $this->assertSame('products_list', $output->catalogRows[0][0]);
        $this->assertSame('0.5000', $output->catalogRows[0][4]);
        $this->assertSame('payment', $output->webhookRows[0][0]);
        $this->assertSame('201.50', $output->webhookRows[0][6]);
    }

    public function test_build_encodes_json_output_when_requested(): void
    {
        $builder = new ObservabilityReportOutputBuilder;

        $output = $builder->build(new ObservabilityReportRunResultDto(
            options: new ObservabilityReportOptionsDto(
                minutes: 60,
                source: 'runtime',
                maxApiSlowRate: null,
                maxWebhookLagWarnRate: null,
                requireApiSamples: false,
                requireWebhookSamples: false,
                json: true,
            ),
            snapshot: [
                'minutes' => 60,
                'source' => 'runtime',
                'api' => [
                    'count' => 0,
                    'avg_duration_ms' => 0.0,
                    'slow_count' => 0,
                ],
                'catalog' => [],
                'webhook' => [],
            ],
            warnings: [],
            violations: [],
        ));

        $this->assertNotNull($output->jsonOutput);
        $this->assertStringContainsString('"minutes": 60', $output->jsonOutput);
        $this->assertSame([], $output->summaryRows);
        $this->assertSame([], $output->catalogRows);
        $this->assertSame([], $output->webhookRows);
    }
}
