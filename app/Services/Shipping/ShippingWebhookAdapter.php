<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Contracts\ShippingGatewayInterface;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Services\Webhook\WebhookProcessorAdapterInterface;
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
    public function verifySignature(array $payload, string $signature): bool
    {
        return $this->gateway->verifyWebhookSignature($payload, $signature);
    }

    /**
     * {@inheritDoc}
     */
    public function extractEventId(array $payload): string
    {
        return $this->gateway->extractEventId($payload);
    }

    /**
     * {@inheritDoc}
     */
    public function processTransition(array $payload): WebhookProcessingOutcome
    {
        $trackingNumber = $this->gateway->extractTrackingNumber($payload);
        if ($trackingNumber === '') {
            throw new DomainException('Tracking number is required.');
        }

        $status = $this->gateway->resolveWebhookStatus($payload);

        $shipment = Shipment::query()
            ->where('provider', $this->receiptProvider())
            ->where('tracking_number', $trackingNumber)
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
            'payload' => array_merge($shipment->payload ?? [], ['webhook' => $payload]),
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
