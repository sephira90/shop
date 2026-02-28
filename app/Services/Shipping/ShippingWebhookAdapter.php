<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Services\Webhook\Dto\WebhookIngressMetadataDto;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Services\Webhook\WebhookProcessorAdapterInterface;
use App\Support\Data\JsonPayload;

final readonly class ShippingWebhookAdapter implements WebhookProcessorAdapterInterface
{
    /**
     * Create shipping webhook adapter.
     */
    public function __construct(
        private ShippingWebhookIngressResolver $shippingWebhookIngressResolver,
        private ShippingWebhookTransitionApplier $shippingWebhookTransitionApplier,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function receiptProvider(): string
    {
        return (string) config('shipping.driver', 'fake-shipping');
    }

    /**
     * {@inheritDoc}
     */
    public function observabilityProvider(): string
    {
        return 'shipping';
    }

    public function prevalidateIngress(JsonPayload $payload, string $signature): WebhookIngressMetadataDto
    {
        $webhookPayload = $this->shippingWebhookIngressResolver->resolve($payload);

        return $this->shippingWebhookIngressResolver->prevalidateResolved($webhookPayload, $signature);
    }

    /**
     * {@inheritDoc}
     */
    public function processTransition(JsonPayload $payload): WebhookProcessingOutcome
    {
        $webhookPayload = $this->shippingWebhookIngressResolver->resolve($payload);

        return $this->shippingWebhookTransitionApplier->apply($webhookPayload, $this->receiptProvider());
    }
}
