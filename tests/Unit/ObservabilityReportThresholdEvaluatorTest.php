<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Observability\Dto\ObservabilityReportOptionsDto;
use App\Support\Observability\ObservabilityReportThresholdEvaluator;
use Tests\TestCase;

class ObservabilityReportThresholdEvaluatorTest extends TestCase
{
    public function test_evaluate_adds_required_sample_violations_when_missing(): void
    {
        $evaluator = new ObservabilityReportThresholdEvaluator;

        $result = $evaluator->evaluate($this->emptySnapshot(), new ObservabilityReportOptionsDto(
            minutes: 60,
            source: 'runtime',
            maxApiSlowRate: null,
            maxWebhookLagWarnRate: null,
            requireApiSamples: true,
            requireWebhookSamples: true,
            json: false,
        ));

        $this->assertSame([], $result->warnings);
        $this->assertSame([
            'Required API samples are missing in selected window.',
            'Required webhook samples are missing in selected window.',
        ], $result->violations);
    }

    public function test_evaluate_reports_api_and_webhook_threshold_violations(): void
    {
        $evaluator = new ObservabilityReportThresholdEvaluator;

        $result = $evaluator->evaluate([
            'minutes' => 60,
            'source' => 'runtime',
            'api' => [
                'count' => 4,
                'avg_duration_ms' => 20.0,
                'slow_count' => 3,
            ],
            'catalog' => [],
            'webhook' => [[
                'provider' => 'payment',
                'count' => 4,
                'processed_count' => 4,
                'duplicate_count' => 0,
                'rejected_count' => 0,
                'avg_duration_ms' => 30.0,
                'avg_lag_ms' => 200.0,
                'lag_warn_count' => 3,
            ]],
        ], new ObservabilityReportOptionsDto(
            minutes: 60,
            source: 'runtime',
            maxApiSlowRate: 0.5,
            maxWebhookLagWarnRate: 0.5,
            requireApiSamples: false,
            requireWebhookSamples: false,
            json: false,
        ));

        $this->assertSame([], $result->warnings);
        $this->assertCount(2, $result->violations);
        $this->assertStringContainsString('API slow rate exceeded', $result->violations[0]);
        $this->assertStringContainsString('Webhook lag-warn rate exceeded for provider payment', $result->violations[1]);
    }

    public function test_evaluate_adds_threshold_skip_warnings_when_no_samples_exist(): void
    {
        $evaluator = new ObservabilityReportThresholdEvaluator;

        $result = $evaluator->evaluate($this->emptySnapshot(), new ObservabilityReportOptionsDto(
            minutes: 60,
            source: 'runtime',
            maxApiSlowRate: 0.3,
            maxWebhookLagWarnRate: 0.3,
            requireApiSamples: false,
            requireWebhookSamples: false,
            json: false,
        ));

        $this->assertSame([
            'API threshold check skipped: no API samples in selected window.',
            'Webhook lag threshold check skipped: no webhook samples in selected window.',
        ], $result->warnings);
        $this->assertSame([], $result->violations);
    }

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
    private function emptySnapshot(): array
    {
        return [
            'minutes' => 60,
            'source' => 'runtime',
            'api' => [
                'count' => 0,
                'avg_duration_ms' => 0.0,
                'slow_count' => 0,
            ],
            'catalog' => [],
            'webhook' => [],
        ];
    }
}
