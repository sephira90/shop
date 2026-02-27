<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use App\Support\Data\JsonPayload;

interface WebhookProcessorAdapterInterface
{
    /**
     * Resolve provider key used for webhook receipt deduplication.
     */
    public function receiptProvider(): string;

    /**
     * Resolve provider key used for observability metrics.
     */
    public function observabilityProvider(): string;

    /**
     * Resolve signature validation error message.
     */
    public function invalidSignatureMessage(): string;

    /**
     * Verify webhook signature.
     */
    public function verifySignature(JsonPayload $payload, string $signature): bool;

    /**
     * Resolve webhook event id.
     */
    public function extractEventId(JsonPayload $payload): string;

    /**
     * Apply provider-specific transition.
     */
    public function processTransition(JsonPayload $payload): WebhookProcessingOutcome;
}
