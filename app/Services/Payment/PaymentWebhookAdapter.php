<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Services\Webhook\Dto\WebhookIngressMetadataDto;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Services\Webhook\WebhookProcessorAdapterInterface;
use App\Support\Data\JsonPayload;

final readonly class PaymentWebhookAdapter implements WebhookProcessorAdapterInterface
{
    /**
     * Create payment webhook adapter.
     */
    public function __construct(
        private PaymentWebhookIngressResolver $paymentWebhookIngressResolver,
        private PaymentWebhookTransitionApplier $paymentWebhookTransitionApplier,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function receiptProvider(): string
    {
        return (string) config('payment.driver', 'fake-payment');
    }

    /**
     * {@inheritDoc}
     */
    public function observabilityProvider(): string
    {
        return 'payment';
    }

    public function prevalidateIngress(JsonPayload $payload, string $signature): WebhookIngressMetadataDto
    {
        $webhookPayload = $this->paymentWebhookIngressResolver->resolve($payload);

        return $this->paymentWebhookIngressResolver->prevalidateResolved($webhookPayload, $signature);
    }

    /**
     * {@inheritDoc}
     */
    public function processTransition(JsonPayload $payload): WebhookProcessingOutcome
    {
        $webhookPayload = $this->paymentWebhookIngressResolver->resolve($payload);

        return $this->paymentWebhookTransitionApplier->apply($webhookPayload, $this->receiptProvider());
    }
}
