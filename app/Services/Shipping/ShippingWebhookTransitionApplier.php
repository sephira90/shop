<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Contracts\ShippingGatewayInterface;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Order\OrderStatusTransitionPolicy;
use App\Services\Shipping\Dto\ShippingWebhookPayloadDto;
use App\Services\Webhook\WebhookIngressException;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Support\Data\TypedValue;

final readonly class ShippingWebhookTransitionApplier
{
    public function __construct(
        private ShippingGatewayInterface $gateway,
        private ShipmentStatusTransitionPolicy $shipmentStatusTransitionPolicy,
        private OrderStatusTransitionPolicy $orderStatusTransitionPolicy,
    ) {}

    public function apply(ShippingWebhookPayloadDto $webhookPayload, string $provider): WebhookProcessingOutcome
    {
        $status = $this->gateway->resolveWebhookStatus($webhookPayload->rawPayload->toArray());

        $shipment = Shipment::query()
            ->where('provider', $provider)
            ->where('tracking_number', $webhookPayload->trackingNumber)
            ->lockForUpdate()
            ->first();

        if (! $shipment instanceof Shipment) {
            throw WebhookIngressException::shipmentNotFound();
        }

        $currentStatus = ShipmentStatus::from(TypedValue::string($shipment->getRawOriginal('status')));
        if (! $this->shipmentStatusTransitionPolicy->canTransition($currentStatus, $status)) {
            return WebhookProcessingOutcome::DUPLICATE;
        }

        /** @var array<string, mixed> $existingPayload */
        $existingPayload = (array) $shipment->getAttribute('payload');

        $shipment->update([
            'status' => $status->value,
            'payload' => array_merge($existingPayload, ['webhook' => $webhookPayload->rawPayload->toArray()]),
            'shipped_at' => in_array($status, [ShipmentStatus::SHIPPED, ShipmentStatus::DELIVERED, ShipmentStatus::RETURNED], true)
                ? ($shipment->shipped_at ?? now())
                : $shipment->shipped_at,
            'delivered_at' => $status === ShipmentStatus::DELIVERED ? now() : $shipment->delivered_at,
        ]);

        $order = Order::query()
            ->whereKey($shipment->order_id)
            ->lockForUpdate()
            ->first();

        if (! $order instanceof Order) {
            throw WebhookIngressException::shipmentOrderNotFound();
        }

        $currentOrderStatus = OrderStatus::from(TypedValue::string($order->getRawOriginal('status')));
        $newStatus = $this->orderStatusTransitionPolicy->resolveByShipmentStatus($currentOrderStatus, $status);

        $order->update([
            'shipment_status' => $status->value,
            'status' => $newStatus->value,
        ]);

        return WebhookProcessingOutcome::PROCESSED;
    }
}
