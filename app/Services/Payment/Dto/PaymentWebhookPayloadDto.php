<?php

declare(strict_types=1);

namespace App\Services\Payment\Dto;

use App\Support\Data\JsonPayload;

final readonly class PaymentWebhookPayloadDto
{
    private function __construct(
        public string $eventId,
        public string $transactionId,
        public ?string $status,
        public JsonPayload $rawPayload,
    ) {}

    /**
     * Build typed webhook payload from resolved provider identifiers.
     */
    public static function fromResolved(JsonPayload $rawPayload, string $eventId, string $transactionId): self
    {
        $rawPayloadData = $rawPayload->toArray();
        $normalizedEventId = trim($eventId);
        $normalizedTransactionId = trim($transactionId);

        $status = null;
        if (array_key_exists('status', $rawPayloadData)) {
            $statusValue = trim((string) $rawPayloadData['status']);
            $status = $statusValue !== '' ? $statusValue : null;
        }

        return new self(
            eventId: $normalizedEventId,
            transactionId: $normalizedTransactionId,
            status: $status,
            rawPayload: $rawPayload,
        );
    }
}
