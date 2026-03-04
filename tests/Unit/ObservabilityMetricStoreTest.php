<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Observability\ObservabilityMetricStore;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ObservabilityMetricStoreTest extends TestCase
{
    public function test_api_metrics_return_raw_window_totals_by_source(): void
    {
        Cache::flush();

        $store = app(ObservabilityMetricStore::class);
        $store->storeApiSample(10.0, 'runtime', 15);
        $store->storeApiSample(25.0, 'runtime', 15);
        $store->storeApiSample(30.0, 'smoke', 15);

        $runtimeMetrics = $store->apiMetrics(60, 'runtime');
        $smokeMetrics = $store->apiMetrics(60, 'smoke');

        $this->assertSame([
            'count' => 2,
            'duration_ms_total' => 35,
            'slow_count' => 1,
        ], $runtimeMetrics);
        $this->assertSame([
            'count' => 1,
            'duration_ms_total' => 30,
            'slow_count' => 1,
        ], $smokeMetrics);
    }

    public function test_catalog_and_webhook_metrics_use_dimension_registries_and_outcome_buckets(): void
    {
        Cache::flush();

        $store = app(ObservabilityMetricStore::class);
        $store->storeCatalogSample('products_list', true, 8.0, 'runtime', 10);
        $store->storeCatalogSample('products_list', false, 15.0, 'runtime', 10);
        $store->storeCatalogSample('products_list', false, 12.0, 'smoke', 10);

        $store->storeWebhookSample('payment', 'processed', 30.0, 200.0, 'runtime', 150);
        $store->storeWebhookSample('payment', 'duplicate', 20.0, null, 'runtime', 150);
        $store->storeWebhookSample('payment', 'processed', 25.0, 90.0, 'smoke', 150);

        $this->assertSame([
            [
                'segment' => 'products_list',
                'count' => 2,
                'hit_count' => 1,
                'miss_count' => 1,
                'duration_ms_total' => 23,
                'slow_miss_count' => 1,
            ],
        ], $store->catalogMetrics(60, 'runtime'));

        $this->assertSame([
            [
                'provider' => 'payment',
                'count' => 2,
                'processed_count' => 1,
                'duplicate_count' => 1,
                'rejected_count' => 0,
                'duration_ms_total' => 50,
                'lag_ms_total' => 200,
                'lag_samples' => 1,
                'lag_warn_count' => 1,
            ],
        ], $store->webhookMetrics(60, 'runtime'));

        $this->assertSame([
            [
                'provider' => 'payment',
                'count' => 1,
                'processed_count' => 1,
                'duplicate_count' => 0,
                'rejected_count' => 0,
                'duration_ms_total' => 25,
                'lag_ms_total' => 90,
                'lag_samples' => 1,
                'lag_warn_count' => 0,
            ],
        ], $store->webhookMetrics(60, 'smoke'));
    }

    public function test_status_transition_metrics_are_grouped_by_domain_and_transition(): void
    {
        Cache::flush();

        $store = app(ObservabilityMetricStore::class);
        $store->storeStatusTransitionSample('order', 'pending', 'paid', 'runtime');
        $store->storeStatusTransitionSample('order', 'pending', 'paid', 'runtime');
        $store->storeStatusTransitionSample('payment', 'pending', 'captured', 'runtime');
        $store->storeStatusTransitionSample('order', 'pending', 'paid', 'smoke');

        $this->assertSame([
            [
                'domain' => 'order',
                'previous_status' => 'pending',
                'current_status' => 'paid',
                'count' => 2,
            ],
            [
                'domain' => 'payment',
                'previous_status' => 'pending',
                'current_status' => 'captured',
                'count' => 1,
            ],
        ], $store->statusTransitionMetrics(60, 'runtime'));

        $this->assertSame([
            [
                'domain' => 'order',
                'previous_status' => 'pending',
                'current_status' => 'paid',
                'count' => 1,
            ],
        ], $store->statusTransitionMetrics(60, 'smoke'));
    }
}
