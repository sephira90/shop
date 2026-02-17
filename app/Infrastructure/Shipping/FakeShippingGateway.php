<?php

declare(strict_types=1);

namespace App\Infrastructure\Shipping;

use App\Contracts\ShippingGatewayInterface;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use Illuminate\Support\Str;

final class FakeShippingGateway implements ShippingGatewayInterface
{
    /**
     * {@inheritDoc}
     */
    public function createShipment(Order $order): array
    {
        return [
            'tracking_number' => 'TRK'.Str::upper(Str::random(12)),
            'status' => ShipmentStatus::PACKED,
            'cost' => 7.50,
            'payload' => [
                'provider' => 'fake-shipping',
                'order_id' => $order->id,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        $expected = hash('sha256', (string) ($payload['event_id'] ?? ''));

        return hash_equals($expected, $signature);
    }

    /**
     * {@inheritDoc}
     */
    public function extractEventId(array $payload): string
    {
        return (string) ($payload['event_id'] ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function extractTrackingNumber(array $payload): string
    {
        return (string) ($payload['tracking_number'] ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function resolveWebhookStatus(array $payload): ShipmentStatus
    {
        $status = (string) ($payload['status'] ?? 'pending');

        return match ($status) {
            'packed' => ShipmentStatus::PACKED,
            'shipped' => ShipmentStatus::SHIPPED,
            'delivered' => ShipmentStatus::DELIVERED,
            'returned' => ShipmentStatus::RETURNED,
            default => ShipmentStatus::PENDING,
        };
    }
}
