<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentStatus;
use App\Jobs\DispatchShipmentJob;
use App\Jobs\SendOrderConfirmationJob;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderStatusTransitionPolicy;
use App\Services\Payment\Dto\PaymentWebhookPayloadDto;
use App\Services\Webhook\Dto\WebhookIngressMetadataDto;
use App\Services\Webhook\WebhookIngressException;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Services\Webhook\WebhookProcessorAdapterInterface;
use App\Support\Data\JsonPayload;

final readonly class PaymentWebhookAdapter implements WebhookProcessorAdapterInterface
{
    /**
     * Create payment webhook adapter.
     */
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private PaymentStatusTransitionPolicy $paymentStatusTransitionPolicy,
        private OrderStatusTransitionPolicy $orderStatusTransitionPolicy,
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
        $webhookPayload = $this->parsePayload($payload);

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

    /**
     * {@inheritDoc}
     */
    public function processTransition(JsonPayload $payload): WebhookProcessingOutcome
    {
        $webhookPayload = $this->parsePayload($payload);

        $paymentStatus = $this->gateway->resolveWebhookStatus($webhookPayload->rawPayload->toArray());

        $payment = Payment::query()
            ->where('gateway', $this->receiptProvider())
            ->where('transaction_id', $webhookPayload->transactionId)
            ->lockForUpdate()
            ->first();

        if (! $payment instanceof Payment) {
            throw WebhookIngressException::paymentNotFound();
        }

        $previousPaymentStatus = PaymentStatus::from((string) $payment->getRawOriginal('status'));

        if (! $this->paymentStatusTransitionPolicy->canTransition($previousPaymentStatus, $paymentStatus)) {
            return WebhookProcessingOutcome::DUPLICATE;
        }

        $payment->update([
            'status' => $paymentStatus->value,
            'payload' => array_merge($payment->payload ?? [], ['webhook' => $webhookPayload->rawPayload->toArray()]),
            'processed_at' => now(),
        ]);

        $order = $payment->order;
        if (! $order instanceof Order) {
            throw WebhookIngressException::paymentOrderNotFound();
        }

        $orderStatus = $this->orderStatusTransitionPolicy->resolveByPaymentStatus($order->status, $paymentStatus);

        $order->update([
            'payment_status' => $paymentStatus->value,
            'status' => $orderStatus->value,
        ]);

        if ($paymentStatus === PaymentStatus::CAPTURED && $previousPaymentStatus !== PaymentStatus::CAPTURED) {
            SendOrderConfirmationJob::dispatch($order->id)->afterCommit();
            DispatchShipmentJob::dispatch($order->id)->afterCommit();
        }

        return WebhookProcessingOutcome::PROCESSED;
    }

    /**
     * Parse raw payload into typed webhook DTO.
     */
    private function parsePayload(JsonPayload $payload): PaymentWebhookPayloadDto
    {
        return PaymentWebhookPayloadDto::fromResolved(
            rawPayload: $payload,
            eventId: $this->gateway->extractEventId($payload->toArray()),
            transactionId: $this->gateway->extractTransactionId($payload->toArray()),
        );
    }
}
