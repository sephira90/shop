<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Observability\ObservabilityService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ObservabilityServiceTest extends TestCase
{
    /**
     * Ensure API request metric is logged when observability is enabled.
     */
    public function test_api_request_is_logged_when_enabled(): void
    {
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'stack');
        config()->set('observability.api.slow_ms', 1);

        Log::shouldReceive('channel')->twice()->with('stack')->andReturnSelf();
        Log::shouldReceive('info')->once()->with(
            'observability.api_request',
            \Mockery::on(static fn (array $payload): bool => $payload['metric'] === 'api.request'
                && $payload['source'] === 'runtime'
                && $payload['status'] === 200
                && $payload['method'] === 'GET'),
        );
        Log::shouldReceive('warning')->once()->with(
            'observability.api_request_slow',
            \Mockery::type('array'),
        );

        $service = app(ObservabilityService::class);
        $service->apiRequest('get', '/api/v1/catalog/products', 200, 10.0);
    }

    /**
     * Ensure no metrics are logged when observability is disabled.
     */
    public function test_metrics_are_not_logged_when_disabled(): void
    {
        config()->set('observability.enabled', false);

        Log::shouldReceive('channel')->never();

        $service = app(ObservabilityService::class);
        $service->catalogCache('products_list', true, 0.5, 10);
        $service->webhook('payment', 'evt-test', 'processed', 1.0, 0.5);
    }

    /**
     * Ensure snapshot is scoped by metric source.
     */
    public function test_snapshot_filters_metrics_by_source(): void
    {
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'null');
        config()->set('observability.api.slow_ms', 1);
        config()->set('observability.webhook.lag_warn_ms', 1);

        Cache::flush();

        $service = app(ObservabilityService::class);
        $service->apiRequest('GET', '/api/v1/catalog/products', 200, 10.0, 'runtime');
        $service->apiRequest('GET', '/api/v1/catalog/products', 200, 15.0, 'smoke');
        $service->webhook('payment', 'evt-runtime', 'processed', 10.0, 10.0, 'runtime');
        $service->webhook('payment', 'evt-smoke', 'processed', 15.0, 15.0, 'smoke');

        $runtimeSnapshot = $service->snapshot(60, 'runtime');
        $smokeSnapshot = $service->snapshot(60, 'smoke');

        $this->assertSame('runtime', $runtimeSnapshot['source']);
        $this->assertSame(1, $runtimeSnapshot['api']['count']);
        $this->assertSame(1, $runtimeSnapshot['api']['slow_count']);
        $this->assertSame(1, $runtimeSnapshot['webhook'][0]['count']);
        $this->assertSame(1, $runtimeSnapshot['webhook'][0]['lag_warn_count']);

        $this->assertSame('smoke', $smokeSnapshot['source']);
        $this->assertSame(1, $smokeSnapshot['api']['count']);
        $this->assertSame(1, $smokeSnapshot['api']['slow_count']);
        $this->assertSame(1, $smokeSnapshot['webhook'][0]['count']);
        $this->assertSame(1, $smokeSnapshot['webhook'][0]['lag_warn_count']);
    }
}
