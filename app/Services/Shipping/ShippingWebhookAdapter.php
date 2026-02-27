<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Contracts\ShippingGatewayInterface;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Shipping\Dto\ShippingWebhookPayloadDto;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Services\Webhook\WebhookProcessorAdapterInterface;
use App\Support\Data\JsonPayload;
use DomainException;

final readonly class ShippingWebhookAdapter implements WebhookProcessorAdapterInterface
{
    /**
     * Create shipping webhook adapter.
     */
    public function __construct(
        private ShippingGatewayInterface $gateway,
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

    /**
     * {@inheritDoc}
     */
    public function invalidSignatureMessage(): string
    {
        return 'Invalid shipping webhook signature.';
    }

    /**
     * {@inheritDoc}
     */
    public function verifySignature(JsonPayload $payload, string $signature): bool
    {
        $webhookPayload = $this->parsePayload($payload);

        return $this->gateway->verifyWebhookSignature($webhookPayload->rawPayload->toArray(), $signature);
    }

    /**
     * {@inheritDoc}
     */
    public function extractEventId(JsonPayload $payload): string
    {
        return $this->parsePayload($payload)->eventId;
    }

    /**
     * {@inheritDoc}
     */
    public function processTransition(JsonPayload $payload): WebhookProcessingOutcome
    {
        $webhookPayload = $this->parsePayload($payload);

        if ($webhookPayload->trackingNumber === '') {
            throw new DomainException('Tracking number is required.');
        }

        $status = $this->gateway->resolveWebhookStatus($webhookPayload->rawPayload->toArray());

        $shipment = Shipment::query()
            ->where('provider', $this->receiptProvider())
            ->where('tracking_number', $webhookPayload->trackingNumber)
            ->lockForUpdate()
            ->first();

        if (! $shipment instanceof Shipment) {
            throw new DomainException('Shipment not found for tracking number.');
        }

        $currentStatus = ShipmentStatus::from((string) $shipment->getRawOriginal('status'));
        if (! $this->shouldApplyShipmentStatusTransition($currentStatus, $status)) {
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

            $newStatus = $currentOrderStatus;
            if ($status === ShipmentStatus::DELIVERED && $currentOrderStatus !== OrderStatus::CANCELLED) {
                $newStatus = OrderStatus::COMPLETED;
            }

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

    /**
     * Validate shipment status transition.
     */
    private function shouldApplyShipmentStatusTransition(ShipmentStatus $from, ShipmentStatus $to): bool
    {
        return match ($from) {
            ShipmentStatus::PENDING => in_array($to, [
                ShipmentStatus::PENDING,
                ShipmentStatus::PACKED,
                ShipmentStatus::SHIPPED,
                ShipmentStatus::DELIVERED,
            ], true),
            ShipmentStatus::PACKED => in_array($to, [
                ShipmentStatus::PACKED,
                ShipmentStatus::SHIPPED,
                ShipmentStatus::DELIVERED,
                ShipmentStatus::RETURNED,
            ], true),
            ShipmentStatus::SHIPPED => in_array($to, [
                ShipmentStatus::SHIPPED,
                ShipmentStatus::DELIVERED,
                ShipmentStatus::RETURNED,
            ], true),
            ShipmentStatus::DELIVERED => in_array($to, [
                ShipmentStatus::DELIVERED,
                ShipmentStatus::RETURNED,
            ], true),
            ShipmentStatus::RETURNED => $to === ShipmentStatus::RETURNED,
        };
    }
}
