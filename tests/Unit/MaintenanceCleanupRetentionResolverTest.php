<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Maintenance\MaintenanceCleanupRetentionResolver;
use InvalidArgumentException;
use Tests\TestCase;

class MaintenanceCleanupRetentionResolverTest extends TestCase
{
    public function test_resolve_prefers_positive_option_overrides(): void
    {
        $resolver = new MaintenanceCleanupRetentionResolver;

        $retention = $resolver->resolve([
            'idempotency-retain-hours' => '24',
            'webhook-retain-hours' => '48',
            'active-cart-retain-hours' => '72',
            'inactive-cart-retain-hours' => '96',
        ]);

        $this->assertSame(24, $retention->idempotencyHours);
        $this->assertSame(48, $retention->webhookHours);
        $this->assertSame(72, $retention->activeCartHours);
        $this->assertSame(96, $retention->inactiveCartHours);
    }

    public function test_resolve_rejects_invalid_positive_integer_option(): void
    {
        $resolver = new MaintenanceCleanupRetentionResolver;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option --webhook-retain-hours must be a positive integer.');

        $resolver->resolve([
            'idempotency-retain-hours' => null,
            'webhook-retain-hours' => '0',
            'active-cart-retain-hours' => null,
            'inactive-cart-retain-hours' => null,
        ]);
    }

    public function test_resolve_rejects_non_positive_config_fallback(): void
    {
        config()->set('cleanup.retention.idempotency_hours', 0);

        $resolver = new MaintenanceCleanupRetentionResolver;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configured "idempotency-retain-hours" must be greater than 0.');

        $resolver->resolve([
            'idempotency-retain-hours' => null,
            'webhook-retain-hours' => null,
            'active-cart-retain-hours' => null,
            'inactive-cart-retain-hours' => null,
        ]);
    }
}
