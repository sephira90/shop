<?php

declare(strict_types=1);

namespace App\Support\Orders;

use App\Support\Data\TypedValue;
use App\Support\Orders\Dto\OrdersReconcileOptionsDto;
use InvalidArgumentException;

final class OrdersReconcileOptionsResolver
{
    /**
     * @param  array{
     *     stuck_shipment_minutes:mixed,
     *     stale_pending_payment_minutes:mixed,
     *     failed_jobs_threshold:mixed,
     *     json:mixed
     * }  $options
     */
    public function resolve(array $options): OrdersReconcileOptionsDto
    {
        return new OrdersReconcileOptionsDto(
            stuckShipmentMinutes: $this->resolvePositiveInt(
                $options['stuck_shipment_minutes'],
                'stuck-shipment-minutes',
                TypedValue::int(config('orders.reconciliation.stuck_shipment_minutes', 90)),
            ),
            stalePendingPaymentMinutes: $this->resolvePositiveInt(
                $options['stale_pending_payment_minutes'],
                'stale-pending-payment-minutes',
                TypedValue::int(config('orders.reconciliation.stale_pending_payment_minutes', 60)),
            ),
            failedJobsThreshold: $this->resolvePositiveInt(
                $options['failed_jobs_threshold'],
                'failed-jobs-threshold',
                TypedValue::int(config('orders.reconciliation.failed_jobs_threshold', 1)),
            ),
            json: (bool) $options['json'],
        );
    }

    private function resolvePositiveInt(mixed $raw, string $optionKey, int $default): int
    {
        if ($raw === null || $raw === '') {
            return $default;
        }

        if (is_numeric($raw) === false || (int) $raw <= 0) {
            throw new InvalidArgumentException(sprintf('Option --%s must be a positive integer.', $optionKey));
        }

        return (int) $raw;
    }
}
