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
        $order->update([
            'status' => $payload['status'] ?? $order->status,
            'payment_status' => $payload['payment_status'] ?? $order->payment_status,
            'shipment_status' => $payload['shipment_status'] ?? $order->shipment_status,
            'cancelled_at' => ($payload['status'] ?? null) === 'cancelled' ? now() : null,
        ]);

        return $order->fresh(['items', 'payments', 'shipments', 'user']);
    }
}
