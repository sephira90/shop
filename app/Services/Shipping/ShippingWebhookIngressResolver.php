<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Contracts\ShippingGatewayInterface;
use App\Services\Shipping\Dto\ShippingWebhookPayloadDto;
use App\Services\Webhook\Dto\WebhookIngressMetadataDto;
use App\Services\Webhook\WebhookIngressException;
use App\Support\Data\JsonPayload;

final readonly class ShippingWebhookIngressResolver
{
    public function __construct(
        private ShippingGatewayInterface $gateway,
    ) {}

    public function resolve(JsonPayload $payload): ShippingWebhookPayloadDto
    {
        $payloadData = $payload->toArray();

        return ShippingWebhookPayloadDto::fromResolved(
            rawPayload: $payload,
            eventId: $this->gateway->extractEventId($payloadData),
            trackingNumber: $this->gateway->extractTrackingNumber($payloadData),
        );
    }

    public function prevalidateResolved(ShippingWebhookPayloadDto $webhookPayload, string $signature): WebhookIngressMetadataDto
    {
        if (! $this->gateway->verifyWebhookSignature($webhookPayload->rawPayload->toArray(), $signature)) {
            throw WebhookIngressException::invalidSignature('Invalid shipping webhook signature.');
        }

        if ($webhookPayload->eventId === '') {
            throw WebhookIngressException::missingEventId();
        }

        if ($webhookPayload->trackingNumber === '') {
            throw WebhookIngressException::missingShippingTrackingNumber();
        }

        return new WebhookIngressMetadataDto($webhookPayload->eventId);
    }
}
