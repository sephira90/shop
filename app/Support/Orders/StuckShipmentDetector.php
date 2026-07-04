<?php

declare(strict_types=1);

namespace App\Support\Orders;

use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Support\Orders\Dto\OrdersReconcileFindingDto;
use App\Support\Orders\Dto\OrdersReconcileOptionsDto;
use Carbon\CarbonImmutable;

/**
 * Surfaces paid orders whose shipment has not advanced beyond PENDING for
 * longer than the configured window. The DispatchShipmentJob is dispatched
 * afterCommit on a captured payment; a queue backend outage at that point
 * or exhausted retries strand the order silently, so this detector is the
 * only operational signal that the side effect never happened.
 */
final class StuckShipmentDetector implements OrdersReconcileDetector
{
    /**
     * @return list<OrdersReconcileFindingDto>
     */
    public function detect(OrdersReconcileOptionsDto $options): array
    {
        $threshold = CarbonImmutable::now()->subMinutes($options->stuckShipmentMinutes);

        $rows = Order::query()
            ->from('orders')
            ->select(['orders.id', 'orders.order_number', 'orders.placed_at'])
            ->leftJoin('shipments', 'shipments.order_id', '=', 'orders.id')
            ->where('orders.payment_status', PaymentStatus::CAPTURED->value)
            ->where('orders.shipment_status', ShipmentStatus::PENDING->value)
            ->whereNull('shipments.id')
            ->where('orders.placed_at', '<=', $threshold)
            ->limit(500)
            ->get();

        $findings = [];

        foreach ($rows as $row) {
            $ageMinutes = (int) CarbonImmutable::parse($row->placed_at)->diffInMinutes(CarbonImmutable::now(), false);

            $findings[] = new OrdersReconcileFindingDto(
                kind: 'stuck_shipment',
                orderId: $row->id,
                orderNumber: $row->order_number,
                ageMinutes: $ageMinutes,
            );
        }

        return $findings;
    }
}
