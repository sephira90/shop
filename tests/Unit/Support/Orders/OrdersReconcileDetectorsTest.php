<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Orders;

use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Support\Orders\Dto\OrdersReconcileOptionsDto;
use App\Support\Orders\FailedJobsDetector;
use App\Support\Orders\StalePendingPaymentDetector;
use App\Support\Orders\StuckShipmentDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifies each reconciliation detector surfaces only the stuck-state class
 * it owns, with the documented window semantics: the stuck-shipment
 * detector keys off payment status + shipment status, the stale pending
 * payment detector keys off payment status + created_at, and the failed
 * jobs detector reports the queue.failed_jobs row count against threshold.
 */
class OrdersReconcileDetectorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stuck_shipment_detector_reports_paid_orders_without_advanced_shipment(): void
    {
        $now = Carbon::parse('2026-07-04 12:00:00');
        Carbon::setTestNow($now);
        try {
            // Paid order without shipment record older than the window.
            $stuckOrder = Order::factory()->paid()->create();
            $stuckOrder->update(['placed_at' => $now->copy()->subMinutes(120)]);

            // Paid order inside the window: not reported.
            Order::factory()->paid()->create(['placed_at' => $now->copy()->subMinutes(10)]);

            // Paid order with a SHIPPED shipment: not reported.
            $shippedOrder = Order::factory()->paid()->create(['placed_at' => $now->copy()->subMinutes(120)]);
            Shipment::unguarded(fn (): Shipment => Shipment::query()->create([
                'order_id' => $shippedOrder->id,
                'provider' => 'ups',
                'status' => ShipmentStatus::SHIPPED->value,
                'cost' => 5.00,
            ]));

            // Pending order without shipment: not reported (payment not captured).
            Order::factory()->create(['placed_at' => $now->copy()->subMinutes(120)]);

            $findings = app(StuckShipmentDetector::class)->detect($this->makeOptions(stuckShipmentMinutes: 90));

            $this->assertCount(1, $findings);
            $this->assertSame($stuckOrder->id, $findings[0]->orderId);
            $this->assertSame($stuckOrder->order_number, $findings[0]->orderNumber);
            $this->assertSame(120, $findings[0]->ageMinutes);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_stale_pending_payment_detector_reports_old_pending_payments(): void
    {
        $now = Carbon::parse('2026-07-04 12:00:00');
        Carbon::setTestNow($now);
        try {
            // Pending order older than the window.
            $staleOrder = Order::factory()->create([
                'payment_status' => PaymentStatus::PENDING->value,
                'placed_at' => $now->copy()->subMinutes(120),
            ]);

            // Pending order inside the window: not reported.
            Order::factory()->create([
                'payment_status' => PaymentStatus::PENDING->value,
                'placed_at' => $now->copy()->subMinutes(10),
            ]);

            // Captured order older than the window: not reported.
            Order::factory()->paid()->create(['placed_at' => $now->copy()->subMinutes(120)]);

            $findings = app(StalePendingPaymentDetector::class)->detect($this->makeOptions(stalePendingPaymentMinutes: 60));

            $this->assertCount(1, $findings);
            $this->assertSame($staleOrder->id, $findings[0]->orderId);
            $this->assertSame($staleOrder->order_number, $findings[0]->orderNumber);
            $this->assertSame(120, $findings[0]->ageMinutes);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_failed_jobs_detector_reports_failed_jobs_table_above_threshold(): void
    {
        $this->seedFailedJobsRow();
        $this->seedFailedJobsRow();

        $findings = app(FailedJobsDetector::class)->detect($this->makeOptions(failedJobsThreshold: 2));

        $this->assertCount(1, $findings);
        $this->assertSame(2, $findings[0]->count);
    }

    public function test_failed_jobs_detector_reports_nothing_below_threshold(): void
    {
        $this->seedFailedJobsRow();

        $findings = app(FailedJobsDetector::class)->detect($this->makeOptions(failedJobsThreshold: 5));

        $this->assertSame([], $findings);
    }

    private function seedFailedJobsRow(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\TestJob'], JSON_THROW_ON_ERROR),
            'exception' => 'runtime error',
            'failed_at' => now(),
        ]);
    }

    private function makeOptions(
        int $stuckShipmentMinutes = 90,
        int $stalePendingPaymentMinutes = 60,
        int $failedJobsThreshold = 1,
    ): OrdersReconcileOptionsDto {
        return new OrdersReconcileOptionsDto(
            stuckShipmentMinutes: $stuckShipmentMinutes,
            stalePendingPaymentMinutes: $stalePendingPaymentMinutes,
            failedJobsThreshold: $failedJobsThreshold,
            json: false,
        );
    }
}
