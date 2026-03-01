<?php

declare(strict_types=1);

namespace App\Infrastructure\Shipping;

use App\Contracts\ShippingGatewayInterface;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Services\Shipping\Dto\ShipmentCreationResultDto;
use App\Support\Data\JsonPayload;
use App\Support\Data\TypedValue;
use Illuminate\Support\Str;

final class FakeShippingGateway implements ShippingGatewayInterface
{
    /**
     * {@inheritDoc}
     */
    public function createShipment(Order $order): ShipmentCreationResultDto
    {
        return new ShipmentCreationResultDto(
            trackingNumber: 'TRK'.Str::upper(Str::random(12)),
            status: ShipmentStatus::PACKED,
            cost: 7.50,
            payload: JsonPayload::fromArray([
                'provider' => 'fake-shipping',
                'order_id' => $order->id,
            ]),
        );
    }

    /**
     * {@inheritDoc}
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        $expected = hash('sha256', TypedValue::string($payload['event_id'] ?? ''));

        return hash_equals($expected, $signature);
    }

    /**
     * {@inheritDoc}
     *
     * @param  array<string, mixed>  $payload
     */
    public function extractEventId(array $payload): string
    {
        return TypedValue::string($payload['event_id'] ?? '');
    }

    /**
     * {@inheritDoc}
     *
     * @param  array<string, mixed>  $payload
     */
    public function extractTrackingNumber(array $payload): string
    {
        return TypedValue::string($payload['tracking_number'] ?? '');
    }

    /**
     * {@inheritDoc}
     *
     * @param  array<string, mixed>  $payload
     */
    public function resolveWebhookStatus(array $payload): ShipmentStatus
    {
        $status = TypedValue::string($payload['status'] ?? 'pending');

        return match ($status) {
            'packed' => ShipmentStatus::PACKED,
            'shipped' => ShipmentStatus::SHIPPED,
            'delivered' => ShipmentStatus::DELIVERED,
            'returned' => ShipmentStatus::RETURNED,
            default => ShipmentStatus::PENDING,
        };
    }
}
