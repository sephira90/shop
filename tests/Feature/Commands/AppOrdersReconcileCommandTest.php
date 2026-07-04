<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * End-to-end coverage for app:orders-reconcile across the documented
 * stuck-state classes. Each scenario exercises the full command wiring
 * (resolver -> runner -> output builder) against real database rows so
 * the integration contract is enforced, not just the unit contract.
 */
class AppOrdersReconcileCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_clean_state_exits_success_and_reports_no_findings(): void
    {
        $this->artisanCommand('app:orders-reconcile')
            ->expectsOutputToContain('No order lifecycle stuck-state findings.')
            ->expectsOutputToContain('Order lifecycle reconciliation: no findings.')
            ->assertSuccessful();
    }

    public function test_clean_state_json_output_marks_clean_flag_true(): void
    {
        $this->artisanCommand('app:orders-reconcile', ['--json' => true])
            ->expectsOutputToContain('"clean": true')
            ->assertSuccessful();
    }

    public function test_stuck_shipment_is_reported_and_command_fails(): void
    {
        $now = Carbon::parse('2026-07-04 12:00:00');
        Carbon::setTestNow($now);
        try {
            Order::factory()->paid()->create(['placed_at' => $now->copy()->subMinutes(120)]);

            $this->artisanCommand('app:orders-reconcile')
                ->expectsOutputToContain('stuck_shipment')
                ->expectsOutputToContain('Order lifecycle reconciliation: stuck-state detected.')
                ->assertFailed();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_stale_pending_payment_is_reported_and_command_fails(): void
    {
        $now = Carbon::parse('2026-07-04 12:00:00');
        Carbon::setTestNow($now);
        try {
            Order::factory()->create([
                'status' => OrderStatus::PENDING->value,
                'payment_status' => PaymentStatus::PENDING->value,
                'placed_at' => $now->copy()->subMinutes(120),
            ]);

            $this->artisanCommand('app:orders-reconcile')
                ->expectsOutputToContain('stale_pending_payment')
                ->assertFailed();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_failed_jobs_above_threshold_are_reported_and_command_fails(): void
    {
        $this->seedFailedJobsRow();
        $this->seedFailedJobsRow();

        $this->artisanCommand('app:orders-reconcile', ['--failed-jobs-threshold' => 2])
            ->expectsOutputToContain('failed_jobs')
            ->assertFailed();
    }

    public function test_invalid_option_exits_failure_with_message(): void
    {
        $this->artisanCommand('app:orders-reconcile', ['--stuck-shipment-minutes' => 0])
            ->expectsOutputToContain('Option --stuck-shipment-minutes must be a positive integer.')
            ->assertFailed();
    }

    public function test_paid_order_with_shipped_shipment_is_not_reported(): void
    {
        $now = Carbon::parse('2026-07-04 12:00:00');
        Carbon::setTestNow($now);
        try {
            $shipped = Order::factory()->paid()->create([
                'placed_at' => $now->copy()->subMinutes(120),
                'shipment_status' => ShipmentStatus::SHIPPED->value,
            ]);
            Shipment::query()->create([
                'order_id' => $shipped->id,
                'provider' => 'ups',
                'status' => ShipmentStatus::SHIPPED->value,
                'cost' => 5.00,
            ]);

            $this->artisanCommand('app:orders-reconcile')
                ->expectsOutputToContain('No order lifecycle stuck-state findings.')
                ->assertSuccessful();
        } finally {
            Carbon::setTestNow();
        }
    }

    private function seedFailedJobsRow(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => Str::uuid()->toString(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\TestJob'], JSON_THROW_ON_ERROR),
            'exception' => 'runtime error',
            'failed_at' => now(),
        ]);
    }
}
