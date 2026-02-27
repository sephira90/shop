<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Services\Shipping\Dto\ShipmentCreationResultDto;

interface ShippingGatewayInterface
{
    /**
     * Create shipment in provider and return normalized payload.
     */
    public function createShipment(Order $order): ShipmentCreationResultDto;

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
