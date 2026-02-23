<?php

declare(strict_types=1);

namespace App\Services\Webhook;

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
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifySignature(array $payload, string $signature): bool;

    /**
     * Resolve webhook event id.
     *
     * @param  array<string, mixed>  $payload
     */
    public function extractEventId(array $payload): string;

    /**
     * Apply provider-specific transition.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processTransition(array $payload): WebhookProcessingOutcome;
}
