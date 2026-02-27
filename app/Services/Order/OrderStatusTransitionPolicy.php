<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;

final class OrderStatusTransitionPolicy
{
    /**
     * Resolve order status transition by payment status event.
     */
    public function resolveByPaymentStatus(OrderStatus|string $currentStatus, PaymentStatus $paymentStatus): OrderStatus
    {
        $status = $currentStatus instanceof OrderStatus
            ? $currentStatus
            : OrderStatus::from($currentStatus);

        return match ($paymentStatus) {
            PaymentStatus::CAPTURED => $status === OrderStatus::PENDING ? OrderStatus::PAID : $status,
            PaymentStatus::FAILED => $status === OrderStatus::PENDING ? OrderStatus::CANCELLED : $status,
            PaymentStatus::REFUNDED => OrderStatus::REFUNDED,
            default => $status,
        };
    }

    /**
     * Resolve order status transition by shipment status event.
     */
    public function resolveByShipmentStatus(OrderStatus|string $currentStatus, ShipmentStatus $shipmentStatus): OrderStatus
    {
        $status = $currentStatus instanceof OrderStatus
            ? $currentStatus
            : OrderStatus::from($currentStatus);

        if ($shipmentStatus === ShipmentStatus::DELIVERED && $status !== OrderStatus::CANCELLED) {
            return OrderStatus::COMPLETED;
        }

        return $status;
    }
}
