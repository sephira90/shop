<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\CheckoutIdempotency;
use App\Support\Maintenance\Dto\MaintenanceCleanupResourceResultDto;
use App\Support\Maintenance\Dto\MaintenanceCleanupRetentionDto;
use App\Support\Maintenance\MaintenanceCleanupExecutor;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceCleanupExecutorTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_uses_batched_deletion_and_aggregates_totals(): void
    {
        config()->set('cleanup.batch_size', 2);

        $now = CarbonImmutable::parse('2026-03-01 12:00:00 UTC');
        $retention = new MaintenanceCleanupRetentionDto(
            idempotencyHours: 24,
            webhookHours: 24,
            activeCartHours: 24,
            inactiveCartHours: 24,
        );

        foreach (range(1, 5) as $index) {
            CheckoutIdempotency::query()->create([
                'scope_key' => 'scope:batch-'.$index,
                'idempotency_key' => 'batch-key-'.$index,
                'cart_id' => null,
                'order_id' => null,
                'request_hash' => hash('sha256', 'batch-'.$index),
                'expires_at' => $now->subHours(72),
            ]);
        }

        $fresh = CheckoutIdempotency::query()->create([
            'scope_key' => 'scope:fresh',
            'idempotency_key' => 'fresh-key',
            'cart_id' => null,
            'order_id' => null,
            'request_hash' => hash('sha256', 'fresh'),
            'expires_at' => $now->subHours(2),
        ]);

        $result = $this->app->make(MaintenanceCleanupExecutor::class)->run(
            retention: $retention,
            dryRun: false,
            now: $now,
        );

        /** @var MaintenanceCleanupResourceResultDto|null $resource */
        $resource = collect($result->resources)
            ->firstWhere('resource', 'checkout_idempotencies');

        $this->assertNotNull($resource);
        $this->assertSame(5, $resource->matched);
        $this->assertSame(5, $resource->affected);
        $this->assertSame(3, $resource->batches);
        $this->assertSame(5, $result->totalMatched);
        $this->assertSame(5, $result->totalAffected);
        $this->assertSame(3, $result->totalBatches);
        $this->assertDatabaseHas('checkout_idempotencies', ['id' => $fresh->id]);
        $this->assertSame(1, CheckoutIdempotency::query()->count());
    }
}
