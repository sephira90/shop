<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Application\Admin\Orders\Dto\UpdateAdminOrderStatusInputDto;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Services\Order\OrderStatusTransitionPolicy;
use App\Services\Payment\PaymentStatusTransitionPolicy;
use App\Services\Shipping\ShipmentStatusTransitionPolicy;
use DomainException;

final class AdminOrderService
{
    /**
     * Create admin order service.
     */
    public function __construct(
        private readonly OrderStatusTransitionPolicy $orderStatusTransitionPolicy,
        private readonly PaymentStatusTransitionPolicy $paymentStatusTransitionPolicy,
        private readonly ShipmentStatusTransitionPolicy $shipmentStatusTransitionPolicy,
    ) {}

    /**
     * Update order status tuple.
     */
    public function updateStatus(Order $order, UpdateAdminOrderStatusInputDto $input): Order
    {
        $currentStatus = $this->resolveOrderStatus($order->status);
        $currentPaymentStatus = $this->resolvePaymentStatus($order->payment_status);
        $currentShipmentStatus = $this->resolveShipmentStatus($order->shipment_status);

        $nextPaymentStatus = $input->paymentStatus ?? $currentPaymentStatus;
        $nextShipmentStatus = $input->shipmentStatus ?? $currentShipmentStatus;

        if (
            $input->paymentStatus !== null
            && ! $this->paymentStatusTransitionPolicy->canTransition($currentPaymentStatus, $nextPaymentStatus)
        ) {
            throw new DomainException('Payment status transition is not allowed.');
        }

        if (
            $input->shipmentStatus !== null
            && ! $this->shipmentStatusTransitionPolicy->canTransition($currentShipmentStatus, $nextShipmentStatus)
        ) {
            throw new DomainException('Shipment status transition is not allowed.');
        }

        $nextStatus = $input->status;

        if ($nextStatus === null) {
            $nextStatus = $currentStatus;

            if ($input->paymentStatus !== null) {
                $nextStatus = $this->orderStatusTransitionPolicy->resolveByPaymentStatus($nextStatus, $nextPaymentStatus);
            }

            if ($input->shipmentStatus !== null) {
                $nextStatus = $this->orderStatusTransitionPolicy->resolveByShipmentStatus($nextStatus, $nextShipmentStatus);
            }
        }

        $cancelledAt = $order->cancelled_at;

        if ($nextStatus === OrderStatus::CANCELLED && $cancelledAt === null) {
            $cancelledAt = now();
        }

        $order->update([
            'status' => $nextStatus->value,
            'payment_status' => $nextPaymentStatus->value,
            'shipment_status' => $nextShipmentStatus->value,
            'cancelled_at' => $cancelledAt,
        ]);

        return $order->fresh(['items', 'payments', 'shipments', 'user']);
    }

    private function resolveOrderStatus(mixed $status): OrderStatus
    {
        if ($status instanceof OrderStatus) {
            return $status;
        }

        return OrderStatus::from((string) $status);
    }

    private function resolvePaymentStatus(mixed $status): PaymentStatus
    {
        if ($status instanceof PaymentStatus) {
            return $status;
        }

        return PaymentStatus::from((string) $status);
    }

    private function resolveShipmentStatus(mixed $status): ShipmentStatus
    {
        if ($status instanceof ShipmentStatus) {
            return $status;
        }

        return ShipmentStatus::from((string) $status);
    }
}
