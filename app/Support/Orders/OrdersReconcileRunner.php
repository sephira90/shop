<?php

declare(strict_types=1);

namespace App\Support\Orders;

use App\Support\Orders\Dto\OrdersReconcileOptionsDto;
use App\Support\Orders\Dto\OrdersReconcileRunResultDto;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the registered reconciliation detectors. The runner is
 * orchestration-only: it never mutates state, never dispatches jobs, and
 * never modifies orders. The caller (artisan command or alert check) owns
 * the decision about whether and how to surface findings.
 *
 * Each run emits a structured observability.reconciliation record so the
 * aggregate counts are queryable independently of the alert routing path.
 */
final class OrdersReconcileRunner
{
    /**
     * @param  iterable<OrdersReconcileDetector>  $detectors
     */
    public function __construct(
        private readonly iterable $detectors,
    ) {}

    public function run(OrdersReconcileOptionsDto $options): OrdersReconcileRunResultDto
    {
        $stuckShipments = [];
        $stalePendingPayments = [];
        $failedJobs = [];

        foreach ($this->detectors as $detector) {
            $findings = $detector->detect($options);

            if ($detector instanceof StuckShipmentDetector) {
                $stuckShipments = $findings;
            } elseif ($detector instanceof StalePendingPaymentDetector) {
                $stalePendingPayments = $findings;
            } elseif ($detector instanceof FailedJobsDetector) {
                $failedJobs = $findings;
            }
        }

        $result = new OrdersReconcileRunResultDto(
            options: $options,
            stuckShipments: $stuckShipments,
            stalePendingPayments: $stalePendingPayments,
            failedJobs: $failedJobs,
        );

        $this->emitTelemetry($result);

        return $result;
    }

    private function emitTelemetry(OrdersReconcileRunResultDto $result): void
    {
        $failedJobsCount = $result->failedJobs === [] ? 0 : ($result->failedJobs[0]->count ?? 0);

        Log::info('observability.reconciliation', [
            'clean' => $result->isClean(),
            'stuck_shipments_count' => count($result->stuckShipments),
            'stale_pending_payments_count' => count($result->stalePendingPayments),
            'failed_jobs_count' => $failedJobsCount,
            'stuck_shipment_minutes' => $result->options->stuckShipmentMinutes,
            'stale_pending_payment_minutes' => $result->options->stalePendingPaymentMinutes,
            'failed_jobs_threshold' => $result->options->failedJobsThreshold,
        ]);
    }
}
