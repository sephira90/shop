<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

final class WebhookIngressException extends DomainException
{
    private function __construct(
        string $message,
        public readonly WebhookIngressErrorCode $errorCode,
        private readonly int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY,
    ) {
        parent::__construct($message);
    }

    public static function invalidSignature(string $message): self
    {
        return new self($message, WebhookIngressErrorCode::INVALID_SIGNATURE);
    }

    public static function missingEventId(): self
    {
        return new self('Webhook event id is required.', WebhookIngressErrorCode::MISSING_EVENT_ID);
    }

    public static function missingPaymentTransactionId(): self
    {
        return new self(
            'Payment transaction id is required.',
            WebhookIngressErrorCode::MISSING_PAYMENT_TRANSACTION_ID,
        );
    }

    public static function missingShippingTrackingNumber(): self
    {
        return new self(
            'Tracking number is required.',
            WebhookIngressErrorCode::MISSING_SHIPPING_TRACKING_NUMBER,
        );
    }

    public static function payloadHashMismatch(): self
    {
        return new self('Webhook payload hash mismatch.', WebhookIngressErrorCode::PAYLOAD_HASH_MISMATCH);
    }

    public static function paymentNotFound(): self
    {
        return new self('Payment transaction not found.', WebhookIngressErrorCode::PAYMENT_NOT_FOUND);
    }

    public static function paymentOrderNotFound(): self
    {
        return new self('Payment order not found.', WebhookIngressErrorCode::PAYMENT_ORDER_NOT_FOUND);
    }

    public static function shipmentNotFound(): self
    {
        return new self('Shipment not found for tracking number.', WebhookIngressErrorCode::SHIPMENT_NOT_FOUND);
    }

    public static function rejectedTransition(): self
    {
        return new self(
            'Webhook adapter rejected processing transition.',
            WebhookIngressErrorCode::REJECTED_TRANSITION,
        );
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
