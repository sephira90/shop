<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Application\Admin\Orders\Dto\UpdateAdminOrderStatusInputDto;
use App\Domain\Exceptions\OrderTransitionException;
use App\Domain\Order\StatusTransitionSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Services\Order\OrderInventoryReleaseService;
use App\Services\Order\OrderStatusTransitionPolicy;
use App\Services\Payment\PaymentStatusTransitionPolicy;
use App\Services\Shipping\ShipmentStatusTransitionPolicy;
use App\Support\Data\TypedValue;
use Illuminate\Support\Facades\DB;

final class AdminOrderService
{
    /**
     * Create admin order service.
     */
    public function __construct(
        private readonly OrderStatusTransitionPolicy $orderStatusTransitionPolicy,
        private readonly PaymentStatusTransitionPolicy $paymentStatusTransitionPolicy,
        private readonly ShipmentStatusTransitionPolicy $shipmentStatusTransitionPolicy,
        private readonly OrderInventoryReleaseService $orderInventoryReleaseService,
    ) {}

    /**
     * Update order status tuple.
     */
    public function updateStatus(Order $order, UpdateAdminOrderStatusInputDto $input): Order
    {
        return DB::transaction(function () use ($order, $input): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder instanceof Order) {
                throw OrderTransitionException::orderNotFoundForStatusUpdate();
            }

            $currentStatus = $this->resolveOrderStatus($lockedOrder->status);
            $currentPaymentStatus = $this->resolvePaymentStatus($lockedOrder->payment_status);
            $currentShipmentStatus = $this->resolveShipmentStatus($lockedOrder->shipment_status);

            $nextPaymentStatus = $input->paymentStatus ?? $currentPaymentStatus;
            $nextShipmentStatus = $input->shipmentStatus ?? $currentShipmentStatus;

            if (
                $input->paymentStatus !== null
                && ! $this->paymentStatusTransitionPolicy->canTransition($currentPaymentStatus, $nextPaymentStatus)
            ) {
                throw OrderTransitionException::paymentStatusTransitionNotAllowed();
            }

            if (
                $input->shipmentStatus !== null
                && ! $this->shipmentStatusTransitionPolicy->canTransition($currentShipmentStatus, $nextShipmentStatus)
            ) {
                throw OrderTransitionException::shipmentStatusTransitionNotAllowed();
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

            if (
                $input->status !== null
                && ! $this->orderStatusTransitionPolicy->canTransition($currentStatus, $nextStatus)
            ) {
                throw OrderTransitionException::orderStatusTransitionNotAllowed();
            }

            if ($nextStatus === OrderStatus::CANCELLED && $currentStatus !== OrderStatus::CANCELLED) {
                $this->orderInventoryReleaseService->release($lockedOrder);
            }

            $cancelledAt = $lockedOrder->cancelled_at;

            if ($nextStatus === OrderStatus::CANCELLED && $cancelledAt === null) {
                $cancelledAt = now();
            }

            $lockedOrder->update([
                'status' => $nextStatus->value,
                'payment_status' => $nextPaymentStatus->value,
                'shipment_status' => $nextShipmentStatus->value,
                'cancelled_at' => $cancelledAt,
            ]);

            if ($nextStatus !== $currentStatus) {
                event(new OrderStatusChanged(
                    orderId: $lockedOrder->id,
                    previousStatus: $currentStatus,
                    currentStatus: $nextStatus,
                    source: StatusTransitionSource::ADMIN_ORDER_UPDATE,
                ));
            }

            return $lockedOrder->refresh()->load(['items', 'payments', 'shipments', 'user']);
        });
    }

    private function resolveOrderStatus(mixed $status): OrderStatus
    {
        if ($status instanceof OrderStatus) {
            return $status;
        }

        return OrderStatus::from(TypedValue::string($status));
    }

    private function resolvePaymentStatus(mixed $status): PaymentStatus
    {
        if ($status instanceof PaymentStatus) {
            return $status;
        }

        return PaymentStatus::from(TypedValue::string($status));
    }

    private function resolveShipmentStatus(mixed $status): ShipmentStatus
    {
        if ($status instanceof ShipmentStatus) {
            return $status;
        }

        return ShipmentStatus::from(TypedValue::string($status));
    }
}
