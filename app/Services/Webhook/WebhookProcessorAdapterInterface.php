<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use App\Services\Webhook\Dto\WebhookIngressMetadataDto;
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

    public function prevalidateIngress(JsonPayload $payload, string $signature): WebhookIngressMetadataDto;

    /**
     * Apply provider-specific transition.
     */
    public function processTransition(JsonPayload $payload): WebhookProcessingOutcome;
}
