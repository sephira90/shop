<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Orders;

use App\Support\Orders\Dto\OrdersReconcileFindingDto;
use App\Support\Orders\Dto\OrdersReconcileOptionsDto;
use App\Support\Orders\Dto\OrdersReconcileRunResultDto;
use App\Support\Orders\OrdersReconcileOutputBuilder;
use Tests\TestCase;

/**
 * Verifies the output builder translates a reconciliation result into the
 * human-readable table shape and the machine-readable JSON shape, and that
 * a clean result surfaces an explicit "no findings" signal in both modes.
 */
class OrdersReconcileOutputBuilderTest extends TestCase
{
    public function test_build_table_shape_lists_each_finding_class(): void
    {
        $result = new OrdersReconcileRunResultDto(
            options: $this->makeOptions(json: false),
            stuckShipments: [
                new OrdersReconcileFindingDto(
                    kind: 'stuck_shipment',
                    orderId: 'order-1',
                    orderNumber: 'ORD-1',
                    ageMinutes: 120,
                ),
            ],
            stalePendingPayments: [
                new OrdersReconcileFindingDto(
                    kind: 'stale_pending_payment',
                    orderId: 'order-2',
                    orderNumber: 'ORD-2',
                    ageMinutes: 90,
                ),
            ],
            failedJobs: [
                new OrdersReconcileFindingDto(kind: 'failed_jobs', count: 3),
            ],
        );

        $output = app(OrdersReconcileOutputBuilder::class)->build($result);

        $this->assertNull($output->jsonOutput);
        $this->assertSame(['kind', 'order_number', 'age_minutes', 'count'], $output->findingsHeaders);
        $this->assertSame(
            [
                ['stuck_shipment', 'ORD-1', '120', null],
                ['stale_pending_payment', 'ORD-2', '90', null],
                ['failed_jobs', null, null, '3'],
            ],
            $output->findingsRows,
        );
    }

    public function test_build_table_shape_for_clean_result_emits_explicit_clean_message(): void
    {
        $result = new OrdersReconcileRunResultDto(
            options: $this->makeOptions(json: false),
            stuckShipments: [],
            stalePendingPayments: [],
            failedJobs: [],
        );

        $output = app(OrdersReconcileOutputBuilder::class)->build($result);

        $this->assertNull($output->jsonOutput);
        $this->assertSame([], $output->findingsRows);
        $this->assertSame('No order lifecycle stuck-state findings.', $output->cleanMessage);
    }

    public function test_build_json_shape_serializes_findings_by_kind(): void
    {
        $result = new OrdersReconcileRunResultDto(
            options: $this->makeOptions(json: true),
            stuckShipments: [
                new OrdersReconcileFindingDto(
                    kind: 'stuck_shipment',
                    orderId: 'order-1',
                    orderNumber: 'ORD-1',
                    ageMinutes: 120,
                ),
            ],
            stalePendingPayments: [],
            failedJobs: [
                new OrdersReconcileFindingDto(kind: 'failed_jobs', count: 3),
            ],
        );

        $output = app(OrdersReconcileOutputBuilder::class)->build($result);

        $this->assertNotNull($output->jsonOutput);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $output->jsonOutput, true, 512, JSON_THROW_ON_ERROR);
        $stuckShipments = (array) ($decoded['stuck_shipments'] ?? []);
        $this->assertCount(1, $stuckShipments);
        $firstStuck = (array) ($stuckShipments[0] ?? []);
        $this->assertSame('ORD-1', $firstStuck['order_number']);
        $this->assertSame([], (array) ($decoded['stale_pending_payments'] ?? []));
        $failedJobs = (array) ($decoded['failed_jobs'] ?? []);
        $firstFailed = (array) ($failedJobs[0] ?? []);
        $this->assertSame(3, $firstFailed['count']);
        $this->assertFalse((bool) ($decoded['clean'] ?? null));
    }

    public function test_build_json_shape_for_clean_result_marks_clean_flag(): void
    {
        $result = new OrdersReconcileRunResultDto(
            options: $this->makeOptions(json: true),
            stuckShipments: [],
            stalePendingPayments: [],
            failedJobs: [],
        );

        $output = app(OrdersReconcileOutputBuilder::class)->build($result);

        $this->assertNotNull($output->jsonOutput);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $output->jsonOutput, true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue((bool) ($decoded['clean'] ?? null));
        $this->assertSame([], (array) ($decoded['stuck_shipments'] ?? []));
    }

    private function makeOptions(bool $json): OrdersReconcileOptionsDto
    {
        return new OrdersReconcileOptionsDto(
            stuckShipmentMinutes: 90,
            stalePendingPaymentMinutes: 60,
            failedJobsThreshold: 1,
            json: $json,
        );
    }
}
