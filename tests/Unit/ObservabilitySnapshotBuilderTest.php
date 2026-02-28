<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Observability\ObservabilityMetricStore;
use App\Support\Observability\ObservabilitySnapshotBuilder;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ObservabilitySnapshotBuilderTest extends TestCase
{
    public function test_build_formats_snapshot_from_store_metrics(): void
    {
        Cache::flush();

        $store = app(ObservabilityMetricStore::class);
        $store->storeApiSample(10.0, 'runtime', 15);
        $store->storeApiSample(30.0, 'runtime', 15);
        $store->storeCatalogSample('products_list', true, 8.0, 'runtime', 10);
        $store->storeCatalogSample('products_list', false, 12.0, 'runtime', 10);
        $store->storeWebhookSample('payment', 'processed', 30.0, 200.0, 'runtime', 150);
        $store->storeWebhookSample('payment', 'duplicate', 10.0, null, 'runtime', 150);

        $snapshot = app(ObservabilitySnapshotBuilder::class)->build(60, 'runtime');

        $this->assertSame('runtime', $snapshot['source']);
        $this->assertSame(2, $snapshot['api']['count']);
        $this->assertSame(20.0, $snapshot['api']['avg_duration_ms']);
        $this->assertSame(1, $snapshot['api']['slow_count']);
        $this->assertSame('products_list', $snapshot['catalog'][0]['segment']);
        $this->assertSame(2, $snapshot['catalog'][0]['count']);
        $this->assertSame(0.5, $snapshot['catalog'][0]['hit_ratio']);
        $this->assertSame(10.0, $snapshot['catalog'][0]['avg_duration_ms']);
        $this->assertSame('payment', $snapshot['webhook'][0]['provider']);
        $this->assertSame(2, $snapshot['webhook'][0]['count']);
        $this->assertSame(1, $snapshot['webhook'][0]['processed_count']);
        $this->assertSame(1, $snapshot['webhook'][0]['duplicate_count']);
        $this->assertSame(20.0, $snapshot['webhook'][0]['avg_duration_ms']);
        $this->assertSame(200.0, $snapshot['webhook'][0]['avg_lag_ms']);
        $this->assertSame(1, $snapshot['webhook'][0]['lag_warn_count']);
    }
}
