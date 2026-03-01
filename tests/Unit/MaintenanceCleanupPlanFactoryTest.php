<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Maintenance\Dto\MaintenanceCleanupPlanItemDto;
use App\Support\Maintenance\Dto\MaintenanceCleanupRetentionDto;
use App\Support\Maintenance\MaintenanceCleanupPlanFactory;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class MaintenanceCleanupPlanFactoryTest extends TestCase
{
    public function test_build_returns_cleanup_plan_in_expected_order_with_cutoffs(): void
    {
        $factory = $this->app->make(MaintenanceCleanupPlanFactory::class);
        $retention = new MaintenanceCleanupRetentionDto(
            idempotencyHours: 24,
            webhookHours: 48,
            activeCartHours: 72,
            inactiveCartHours: 96,
        );
        $now = CarbonImmutable::parse('2026-03-01 12:00:00 UTC');

        $plan = $factory->build($retention, $now);

        $this->assertSame(
            [
                'checkout_idempotencies',
                'webhook_receipts',
                'active_carts',
                'inactive_carts',
            ],
            array_map(
                static fn (MaintenanceCleanupPlanItemDto $step): string => $step->resource->resource(),
                $plan,
            ),
        );
        $this->assertSame(
            [
                '2026-02-28T12:00:00+00:00',
                '2026-02-27T12:00:00+00:00',
                '2026-02-26T12:00:00+00:00',
                '2026-02-25T12:00:00+00:00',
            ],
            array_map(
                static fn (MaintenanceCleanupPlanItemDto $step): string => $step->cutoff->toIso8601String(),
                $plan,
            ),
        );
    }
}
