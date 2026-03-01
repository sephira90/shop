<?php

declare(strict_types=1);

namespace App\Services\Shipping\Dto;

use App\Support\Data\JsonPayload;
use App\Support\Data\TypedValue;

final readonly class ShippingWebhookPayloadDto
{
    private function __construct(
        public string $eventId,
        public string $trackingNumber,
        public ?string $status,
        public JsonPayload $rawPayload,
    ) {}

    /**
     * Build typed webhook payload from resolved provider identifiers.
     */
    public static function fromResolved(JsonPayload $rawPayload, string $eventId, string $trackingNumber): self
    {
        $rawPayloadData = $rawPayload->toArray();
        $normalizedEventId = trim($eventId);
        $normalizedTrackingNumber = trim($trackingNumber);

        $status = null;
        if (array_key_exists('status', $rawPayloadData)) {
            $statusValue = TypedValue::trimmedString($rawPayloadData['status']);
            $status = $statusValue !== '' ? $statusValue : null;
        }

        return new self(
            eventId: $normalizedEventId,
            trackingNumber: $normalizedTrackingNumber,
            status: $status,
            rawPayload: $rawPayload,
        );
    }
}
