<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Contracts\ShippingGatewayInterface;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\WebhookReceipt;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class ShippingService
{
    /**
     * Create shipping service.
     */
    public function __construct(private ShippingGatewayInterface $gateway) {}

    /**
     * Create shipment for order.
     */
    public function createShipment(Order $order): Shipment
    {
        $result = $this->gateway->createShipment($order);

        return Shipment::query()->create([
            'order_id' => $order->id,
            'provider' => 'fake-shipping',
            'tracking_number' => $result['tracking_number'],
            'status' => $result['status']->value,
            'cost' => $result['cost'],
            'payload' => $result['payload'],
        ]);
    }

    /**
     * Process shipping webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processWebhook(array $payload, string $signature): void
    {
        if (! $this->gateway->verifyWebhookSignature($payload, $signature)) {
            throw new DomainException('Invalid shipping webhook signature.');
        }

        $eventId = $this->gateway->extractEventId($payload);
        if ($eventId === '') {
            throw new DomainException('Webhook event id is required.');
        }

        $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        DB::transaction(function () use ($payload, $eventId, $payloadHash): void {
            $receipt = WebhookReceipt::query()->firstOrCreate(
                ['provider' => 'fake-shipping', 'event_id' => $eventId],
                [
                    'payload_hash' => $payloadHash,
                    'processed_at' => null,
                ],
            );

            $receipt = WebhookReceipt::query()
                ->whereKey($receipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($receipt->payload_hash !== $payloadHash) {
                throw new DomainException('Webhook payload hash mismatch.');
            }

            if ($receipt->processed_at !== null) {
                return;
            }

            $trackingNumber = $this->gateway->extractTrackingNumber($payload);
            if ($trackingNumber === '') {
                throw new DomainException('Tracking number is required.');
            }
            $status = $this->gateway->resolveWebhookStatus($payload);

            $shipment = Shipment::query()
                ->where('tracking_number', $trackingNumber)
                ->lockForUpdate()
                ->first();

            if (! $shipment instanceof Shipment) {
                throw new DomainException('Shipment not found for tracking number.');
            }

            $currentStatus = ShipmentStatus::from((string) $shipment->getRawOriginal('status'));
            if (! $this->shouldApplyShipmentStatusTransition($currentStatus, $status)) {
                $receipt->update(['processed_at' => now()]);

                return;
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

            $receipt->update(['processed_at' => now()]);
        });
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
