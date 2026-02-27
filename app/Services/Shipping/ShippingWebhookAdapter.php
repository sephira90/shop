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
use App\Services\Webhook\Dto\WebhookIngressMetadataDto;
use App\Services\Webhook\WebhookIngressException;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Services\Webhook\WebhookProcessorAdapterInterface;
use App\Support\Data\JsonPayload;

final readonly class ShippingWebhookAdapter implements WebhookProcessorAdapterInterface
{
    /**
     * Create shipping webhook adapter.
     */
    public function __construct(
        private ShippingGatewayInterface $gateway,
        private ShipmentStatusTransitionPolicy $shipmentStatusTransitionPolicy,
        private OrderStatusTransitionPolicy $orderStatusTransitionPolicy,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function receiptProvider(): string
    {
        return (string) config('shipping.driver', 'fake-shipping');
    }

    /**
     * {@inheritDoc}
     */
    public function observabilityProvider(): string
    {
        return 'shipping';
    }

    public function prevalidateIngress(JsonPayload $payload, string $signature): WebhookIngressMetadataDto
    {
        $webhookPayload = $this->parsePayload($payload);

        if (! $this->gateway->verifyWebhookSignature($webhookPayload->rawPayload->toArray(), $signature)) {
            throw WebhookIngressException::invalidSignature('Invalid shipping webhook signature.');
        }

        if ($webhookPayload->eventId === '') {
            throw WebhookIngressException::missingEventId();
        }

        if ($webhookPayload->trackingNumber === '') {
            throw WebhookIngressException::missingShippingTrackingNumber();
        }

        return new WebhookIngressMetadataDto($webhookPayload->eventId);
    }

    /**
     * {@inheritDoc}
     */
    public function processTransition(JsonPayload $payload): WebhookProcessingOutcome
    {
        $webhookPayload = $this->parsePayload($payload);

        $status = $this->gateway->resolveWebhookStatus($webhookPayload->rawPayload->toArray());

        $shipment = Shipment::query()
            ->where('provider', $this->receiptProvider())
            ->where('tracking_number', $webhookPayload->trackingNumber)
            ->lockForUpdate()
            ->first();

        if (! $shipment instanceof Shipment) {
            throw WebhookIngressException::shipmentNotFound();
        }

        $currentStatus = ShipmentStatus::from((string) $shipment->getRawOriginal('status'));
        if (! $this->shipmentStatusTransitionPolicy->canTransition($currentStatus, $status)) {
            return WebhookProcessingOutcome::DUPLICATE;
        }

        $shipment->update([
            'status' => $status->value,
            'payload' => array_merge($shipment->payload ?? [], ['webhook' => $webhookPayload->rawPayload->toArray()]),
            'shipped_at' => in_array($status, [ShipmentStatus::SHIPPED, ShipmentStatus::DELIVERED, ShipmentStatus::RETURNED], true)
                ? ($shipment->shipped_at ?? now())
                : $shipment->shipped_at,
            'delivered_at' => $status === ShipmentStatus::DELIVERED ? now() : $shipment->delivered_at,
        ]);

        $order = $shipment->order;
        if ($order instanceof Order) {
            $currentOrderStatus = OrderStatus::from((string) $order->getRawOriginal('status'));

            $newStatus = $this->orderStatusTransitionPolicy->resolveByShipmentStatus($currentOrderStatus, $status);

            $order->update([
                'shipment_status' => $status->value,
                'status' => $newStatus->value,
            ]);
        }

        return WebhookProcessingOutcome::PROCESSED;
    }

    /**
     * Parse raw payload into typed webhook DTO.
     */
    private function parsePayload(JsonPayload $payload): ShippingWebhookPayloadDto
    {
        return ShippingWebhookPayloadDto::fromResolved(
            rawPayload: $payload,
            eventId: $this->gateway->extractEventId($payload->toArray()),
            trackingNumber: $this->gateway->extractTrackingNumber($payload->toArray()),
        );
    }
}
