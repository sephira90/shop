<?php

declare(strict_types=1);

namespace App\Support\Orders;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Support\Orders\Dto\OrdersReconcileFindingDto;
use App\Support\Orders\Dto\OrdersReconcileOptionsDto;
use Carbon\CarbonImmutable;

/**
 * Surfaces orders whose payment is still in PENDING status past the
 * configured window. Beyond that window, a pending payment typically
 * indicates a missing webhook capture or a stale checkout attempt that
 * never reached the gateway; both need operational attention because the
 * order is neither paid nor cancelled.
 */
final class StalePendingPaymentDetector implements OrdersReconcileDetector
{
    /**
     * @return list<OrdersReconcileFindingDto>
     */
    public function detect(OrdersReconcileOptionsDto $options): array
    {
        $threshold = CarbonImmutable::now()->subMinutes($options->stalePendingPaymentMinutes);

        $rows = Order::query()
            ->select(['id', 'order_number', 'placed_at'])
            ->where('payment_status', PaymentStatus::PENDING->value)
            ->where('placed_at', '<=', $threshold)
            ->limit(500)
            ->get();

        $findings = [];

        foreach ($rows as $row) {
            $ageMinutes = (int) CarbonImmutable::parse($row->placed_at)->diffInMinutes(CarbonImmutable::now(), false);

            $findings[] = new OrdersReconcileFindingDto(
                kind: 'stale_pending_payment',
                orderId: $row->id,
                orderNumber: $row->order_number,
                ageMinutes: $ageMinutes,
            );
        }

        return $findings;
    }
}
