<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Application\Admin\Orders\Dto\UpdateAdminOrderStatusInputDto;
use App\Models\Order;

final class AdminOrderService
{
    /**
     * Update order status tuple.
     */
    public function updateStatus(Order $order, UpdateAdminOrderStatusInputDto $input): Order
    {
        $nextStatus = $input->status ?? $order->status;
        $cancelledAt = $order->cancelled_at;

        if ($nextStatus === 'cancelled' && $cancelledAt === null) {
            $cancelledAt = now();
        }

        $order->update([
            'status' => $nextStatus,
            'payment_status' => $input->paymentStatus ?? $order->payment_status,
            'shipment_status' => $input->shipmentStatus ?? $order->shipment_status,
            'cancelled_at' => $cancelledAt,
        ]);

        return $order->fresh(['items', 'payments', 'shipments', 'user']);
    }
}
