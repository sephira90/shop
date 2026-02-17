<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\ShipmentStatus;
use App\Models\Order;

interface ShippingGatewayInterface
{
    /**
     * Create shipment in provider and return normalized payload.
     *
     * @return array{tracking_number:string,status:ShipmentStatus,cost:float,payload:array<string,mixed>}
     */
    public function createShipment(Order $order): array;

    /**
     * Verify webhook signature from shipping provider.
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;

    /**
     * Resolve webhook event id.
     */
    public function extractEventId(array $payload): string;

    /**
     * Resolve tracking number from provider payload.
     */
    public function extractTrackingNumber(array $payload): string;

    /**
     * Map provider payload to internal shipment status.
     */
    public function resolveWebhookStatus(array $payload): ShipmentStatus;
}
