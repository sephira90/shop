<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Order;

final class AdminOrderService
{
    /**
     * Update order status tuple.
     *
     * @param  array<string, mixed>  $payload
     */
    public function updateStatus(Order $order, array $payload): Order
    {
        $nextStatus = $payload['status'] ?? $order->status;
        $cancelledAt = $order->cancelled_at;

        if ($nextStatus === 'cancelled' && $cancelledAt === null) {
            $cancelledAt = now();
        }

        $order->update([
            'status' => $nextStatus,
            'payment_status' => $payload['payment_status'] ?? $order->payment_status,
            'shipment_status' => $payload['shipment_status'] ?? $order->shipment_status,
            'cancelled_at' => $cancelledAt,
        ]);

        return $order->fresh(['items', 'payments', 'shipments', 'user']);
    }
}
