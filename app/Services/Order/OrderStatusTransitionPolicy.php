<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;

final readonly class OrderStatusTransitionPolicy
{
    /**
     * Validate order status transition matrix.
     */
    public function canTransition(OrderStatus|string $from, OrderStatus|string $to): bool
    {
        $currentStatus = $this->normalizeOrderStatus($from);
        $nextStatus = $this->normalizeOrderStatus($to);

        return in_array($nextStatus, $this->allowedTransitions($currentStatus), true);
    }

    /**
     * Backward-compatible alias for explicit direct admin-driven transition checks.
     */
    public function canTransitionDirectly(OrderStatus|string $from, OrderStatus|string $to): bool
    {
        return $this->canTransition($from, $to);
    }

    /**
     * Resolve order status transition by payment status event.
     */
    public function resolveByPaymentStatus(OrderStatus|string $currentStatus, PaymentStatus $paymentStatus): OrderStatus
    {
        $status = $this->normalizeOrderStatus($currentStatus);

        return match ($paymentStatus) {
            PaymentStatus::CAPTURED => $status === OrderStatus::PENDING ? OrderStatus::PAID : $status,
            PaymentStatus::FAILED => $status === OrderStatus::PENDING ? OrderStatus::CANCELLED : $status,
            PaymentStatus::REFUNDED => OrderStatus::REFUNDED,
            default => $status,
        };
    }

    /**
     * Resolve order status transition by shipment status event.
     *
     * `processing` and `shipped` remain explicit order states for admin/manual transitions.
     * Webhook-driven resolution intentionally collapses to terminal customer-facing outcomes.
     */
    public function resolveByShipmentStatus(OrderStatus|string $currentStatus, ShipmentStatus $shipmentStatus): OrderStatus
    {
        $status = $this->normalizeOrderStatus($currentStatus);

        if ($shipmentStatus === ShipmentStatus::DELIVERED && $status !== OrderStatus::CANCELLED) {
            return OrderStatus::COMPLETED;
        }

        return $status;
    }

    /**
     * @return list<OrderStatus>
     */
    private function allowedTransitions(OrderStatus $status): array
    {
        return match ($status) {
            OrderStatus::PENDING => [
                OrderStatus::PENDING,
                OrderStatus::PAID,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELLED,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::PAID => [
                OrderStatus::PAID,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELLED,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::PROCESSING => [
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELLED,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::SHIPPED => [
                OrderStatus::SHIPPED,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELLED,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::COMPLETED => [
                OrderStatus::COMPLETED,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::CANCELLED => [
                OrderStatus::CANCELLED,
                OrderStatus::PROCESSING,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::REFUNDED => [
                OrderStatus::REFUNDED,
            ],
        };
    }

    private function normalizeOrderStatus(OrderStatus|string $status): OrderStatus
    {
        return $status instanceof OrderStatus
            ? $status
            : OrderStatus::from($status);
    }
}
