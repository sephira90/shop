<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Dto\PaymentWebhookPayloadDto;
use App\Services\Webhook\Dto\WebhookIngressMetadataDto;
use App\Services\Webhook\WebhookIngressException;
use App\Support\Data\JsonPayload;

final readonly class PaymentWebhookIngressResolver
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
    ) {}

    public function resolve(JsonPayload $payload): PaymentWebhookPayloadDto
    {
        $payloadData = $payload->toArray();

        return PaymentWebhookPayloadDto::fromResolved(
            rawPayload: $payload,
            eventId: $this->gateway->extractEventId($payloadData),
            transactionId: $this->gateway->extractTransactionId($payloadData),
        );
    }

    public function prevalidateResolved(PaymentWebhookPayloadDto $webhookPayload, string $signature): WebhookIngressMetadataDto
    {
        if (! $this->gateway->verifyWebhookSignature($webhookPayload->rawPayload->toArray(), $signature)) {
            throw WebhookIngressException::invalidSignature('Invalid webhook signature.');
        }

        if ($webhookPayload->eventId === '') {
            throw WebhookIngressException::missingEventId();
        }

        if ($webhookPayload->transactionId === '') {
            throw WebhookIngressException::missingPaymentTransactionId();
        }

        return new WebhookIngressMetadataDto($webhookPayload->eventId);
    }
}
