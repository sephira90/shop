<?php

declare(strict_types=1);

namespace App\Support\Orders;

use App\Support\Orders\Dto\OrdersReconcileFindingDto;
use App\Support\Orders\Dto\OrdersReconcileOptionsDto;
use Illuminate\Support\Facades\DB;

/**
 * Surfaces a non-empty queue.failed_jobs table once it crosses the
 * configured threshold. The default threshold of 1 means even a single
 * failure is reported: failed jobs are not normal operation, and the
 * side-effect jobs (shipment dispatch, order confirmation, status
 * notifications) are all idempotent, so re-dispatch is the safe action.
 */
final class FailedJobsDetector implements OrdersReconcileDetector
{
    /**
     * @return list<OrdersReconcileFindingDto>
     */
    public function detect(OrdersReconcileOptionsDto $options): array
    {
        $count = (int) DB::table('failed_jobs')->count();

        if ($count < $options->failedJobsThreshold) {
            return [];
        }

        return [
            new OrdersReconcileFindingDto(
                kind: 'failed_jobs',
                count: $count,
            ),
        ];
    }
}
