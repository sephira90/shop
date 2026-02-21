<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Observability\ObservabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ObservabilityReportCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure observability report command prints aggregated metrics.
     */
    public function test_observability_report_command_prints_snapshot_tables(): void
    {
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'null');

        Cache::flush();

        $service = app(ObservabilityService::class);
        $service->apiRequest('GET', '/api/v1/catalog/products', 200, 20.0);
        $service->catalogCache('products_list', true, 8.5, 12);
        $service->catalogCache('products_list', false, 15.0, 12);
        $service->webhook('payment', 'evt-test-payment', 'processed', 30.0, 200.0);

        $this->artisan('app:observability-report --minutes=60')
            ->assertSuccessful()
            ->expectsOutputToContain('api_request_count')
            ->expectsOutputToContain('products_list')
            ->expectsOutputToContain('payment')
            ->expectsOutputToContain('Observability report generated.');
    }

    /**
     * Ensure observability report validates minutes option range.
     */
    public function test_observability_report_command_rejects_invalid_minutes_option(): void
    {
        $this->artisan('app:observability-report --minutes=0')
            ->assertFailed()
            ->expectsOutputToContain('Option --minutes must be between 1 and 1440.');
    }

    /**
     * Ensure observability report command fails when API slow rate is above threshold.
     */
    public function test_observability_report_command_fails_when_api_slow_rate_exceeds_threshold(): void
    {
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'null');
        config()->set('observability.api.slow_ms', 1);

        Cache::flush();

        $service = app(ObservabilityService::class);
        $service->apiRequest('GET', '/api/v1/catalog/products', 200, 5.0);
        $service->apiRequest('GET', '/api/v1/catalog/products', 200, 5.0);

        $this->artisan('app:observability-report --minutes=60 --max-api-slow-rate=0.5')
            ->assertFailed()
            ->expectsOutputToContain('API slow rate exceeded')
            ->expectsOutputToContain('Observability threshold checks failed.');
    }

    /**
     * Ensure observability report validates threshold option format.
     */
    public function test_observability_report_command_rejects_invalid_threshold_option(): void
    {
        $this->artisan('app:observability-report --max-api-slow-rate=foo')
            ->assertFailed()
            ->expectsOutputToContain('Option --max-api-slow-rate must be a number in [0..1].');
    }

    /**
     * Ensure observability report fails when API samples are required but missing.
     */
    public function test_observability_report_command_fails_when_required_api_samples_are_missing(): void
    {
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'null');
        Cache::flush();

        $this->artisan('app:observability-report --minutes=60 --require-api-samples')
            ->assertFailed()
            ->expectsOutputToContain('Required API samples are missing in selected window.')
            ->expectsOutputToContain('Observability threshold checks failed.');
    }

    /**
     * Ensure observability report fails when webhook samples are required but missing.
     */
    public function test_observability_report_command_fails_when_required_webhook_samples_are_missing(): void
    {
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'null');
        Cache::flush();

        $this->artisan('app:observability-report --minutes=60 --require-webhook-samples')
            ->assertFailed()
            ->expectsOutputToContain('Required webhook samples are missing in selected window.')
            ->expectsOutputToContain('Observability threshold checks failed.');
    }
}
