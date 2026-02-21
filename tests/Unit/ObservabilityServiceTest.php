<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Observability\ObservabilityService;
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
                && $payload['status'] === 200
                && $payload['method'] === 'GET'),
        );
        Log::shouldReceive('warning')->once()->with(
            'observability.api_request_slow',
            \Mockery::type('array'),
        );

        $service = new ObservabilityService;
        $service->apiRequest('get', '/api/v1/catalog/products', 200, 10.0);
    }

    /**
     * Ensure no metrics are logged when observability is disabled.
     */
    public function test_metrics_are_not_logged_when_disabled(): void
    {
        config()->set('observability.enabled', false);

        Log::shouldReceive('channel')->never();

        $service = new ObservabilityService;
        $service->catalogCache('products_list', true, 0.5, 10);
        $service->webhook('payment', 'evt-test', 'processed', 1.0, 0.5);
    }
}
