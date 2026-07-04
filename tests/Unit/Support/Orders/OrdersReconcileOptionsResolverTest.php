<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Orders;

use App\Support\Orders\OrdersReconcileOptionsResolver;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Verifies the orders reconcile options resolver maps CLI input plus the
 * bounded config defaults into a typed plan, and rejects out-of-range
 * overrides so a misconfiguration cannot silently disable reconciliation.
 */
class OrdersReconcileOptionsResolverTest extends TestCase
{
    public function test_resolve_uses_documented_config_defaults_when_options_are_blank(): void
    {
        config()->set('orders.reconciliation.stuck_shipment_minutes', 90);
        config()->set('orders.reconciliation.stale_pending_payment_minutes', 60);
        config()->set('orders.reconciliation.failed_jobs_threshold', 1);

        $options = app(OrdersReconcileOptionsResolver::class)->resolve([
            'stuck_shipment_minutes' => null,
            'stale_pending_payment_minutes' => null,
            'failed_jobs_threshold' => null,
            'json' => false,
        ]);

        $this->assertSame(90, $options->stuckShipmentMinutes);
        $this->assertSame(60, $options->stalePendingPaymentMinutes);
        $this->assertSame(1, $options->failedJobsThreshold);
        $this->assertFalse($options->json);
    }

    public function test_resolve_prefers_explicit_cli_option_over_config_default(): void
    {
        config()->set('orders.reconciliation.stuck_shipment_minutes', 90);

        $options = app(OrdersReconcileOptionsResolver::class)->resolve([
            'stuck_shipment_minutes' => 30,
            'stale_pending_payment_minutes' => null,
            'failed_jobs_threshold' => null,
            'json' => false,
        ]);

        $this->assertSame(30, $options->stuckShipmentMinutes);
    }

    public function test_resolve_rejects_non_positive_stuck_shipment_window(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option --stuck-shipment-minutes must be a positive integer.');

        app(OrdersReconcileOptionsResolver::class)->resolve([
            'stuck_shipment_minutes' => 0,
            'stale_pending_payment_minutes' => null,
            'failed_jobs_threshold' => null,
            'json' => false,
        ]);
    }

    public function test_resolve_rejects_negative_stale_pending_payment_window(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option --stale-pending-payment-minutes must be a positive integer.');

        app(OrdersReconcileOptionsResolver::class)->resolve([
            'stuck_shipment_minutes' => null,
            'stale_pending_payment_minutes' => -5,
            'failed_jobs_threshold' => null,
            'json' => false,
        ]);
    }

    public function test_resolve_rejects_non_positive_failed_jobs_threshold(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option --failed-jobs-threshold must be a positive integer.');

        app(OrdersReconcileOptionsResolver::class)->resolve([
            'stuck_shipment_minutes' => null,
            'stale_pending_payment_minutes' => null,
            'failed_jobs_threshold' => 0,
            'json' => false,
        ]);
    }

    public function test_resolve_flags_json_output(): void
    {
        $options = app(OrdersReconcileOptionsResolver::class)->resolve([
            'stuck_shipment_minutes' => null,
            'stale_pending_payment_minutes' => null,
            'failed_jobs_threshold' => null,
            'json' => true,
        ]);

        $this->assertTrue($options->json);
    }
}
