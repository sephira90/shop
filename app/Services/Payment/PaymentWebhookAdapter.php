<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\DispatchShipmentJob;
use App\Jobs\SendOrderConfirmationJob;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Dto\PaymentWebhookPayloadDto;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Services\Webhook\WebhookProcessorAdapterInterface;
use App\Support\Data\JsonPayload;
use DomainException;

final readonly class PaymentWebhookAdapter implements WebhookProcessorAdapterInterface
{
    /**
     * Create payment webhook adapter.
     */
    public function __construct(
        private PaymentGatewayInterface $gateway,
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

    /**
     * {@inheritDoc}
     */
    public function invalidSignatureMessage(): string
    {
        return 'Invalid webhook signature.';
    }

    /**
     * {@inheritDoc}
     */
    public function verifySignature(JsonPayload $payload, string $signature): bool
    {
        $webhookPayload = $this->parsePayload($payload);

        return $this->gateway->verifyWebhookSignature($webhookPayload->rawPayload->toArray(), $signature);
    }

    /**
     * {@inheritDoc}
     */
    public function extractEventId(JsonPayload $payload): string
    {
        return $this->parsePayload($payload)->eventId;
    }

    /**
     * Resolve payment transaction id from payload.
     */
    public function extractTransactionId(JsonPayload $payload): string
    {
        return $this->parsePayload($payload)->transactionId;
    }

    /**
     * {@inheritDoc}
     */
    public function processTransition(JsonPayload $payload): WebhookProcessingOutcome
    {
        $webhookPayload = $this->parsePayload($payload);

        if ($webhookPayload->transactionId === '') {
            throw new DomainException('Payment transaction id is required.');
        }

        $paymentStatus = $this->gateway->resolveWebhookStatus($webhookPayload->rawPayload->toArray());

        $payment = Payment::query()
            ->where('gateway', $this->receiptProvider())
            ->where('transaction_id', $webhookPayload->transactionId)
            ->lockForUpdate()
            ->first();

        if (! $payment instanceof Payment) {
            throw new DomainException('Payment transaction not found.');
        }

        $previousPaymentStatus = PaymentStatus::from((string) $payment->getRawOriginal('status'));

        if (! $this->shouldApplyPaymentStatusTransition($previousPaymentStatus, $paymentStatus)) {
            return WebhookProcessingOutcome::DUPLICATE;
        }

        $payment->update([
            'status' => $paymentStatus->value,
            'payload' => array_merge($payment->payload ?? [], ['webhook' => $webhookPayload->rawPayload->toArray()]),
            'processed_at' => now(),
        ]);

        $order = $payment->order;
        if (! $order instanceof Order) {
            throw new DomainException('Payment order not found.');
        }

        $orderStatus = $this->resolveOrderStatus($order->status, $paymentStatus);

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

    /**
     * Resolve order status transition by payment status event.
     */
    private function resolveOrderStatus(OrderStatus|string $currentStatus, PaymentStatus $paymentStatus): OrderStatus
    {
        $status = $currentStatus instanceof OrderStatus
            ? $currentStatus
            : OrderStatus::from($currentStatus);

        return match ($paymentStatus) {
            PaymentStatus::CAPTURED => $status === OrderStatus::PENDING ? OrderStatus::PAID : $status,
            PaymentStatus::FAILED => $status === OrderStatus::PENDING ? OrderStatus::CANCELLED : $status,
            PaymentStatus::REFUNDED => OrderStatus::REFUNDED,
            default => $status,
        };
    }

    /**
     * Validate allowed payment status transition.
     */
    private function shouldApplyPaymentStatusTransition(PaymentStatus $from, PaymentStatus $to): bool
    {
        return match ($from) {
            PaymentStatus::PENDING => in_array($to, [
                PaymentStatus::PENDING,
                PaymentStatus::AUTHORIZED,
                PaymentStatus::CAPTURED,
                PaymentStatus::FAILED,
            ], true),
            PaymentStatus::AUTHORIZED => in_array($to, [
                PaymentStatus::AUTHORIZED,
                PaymentStatus::CAPTURED,
                PaymentStatus::FAILED,
            ], true),
            PaymentStatus::CAPTURED => in_array($to, [
                PaymentStatus::CAPTURED,
                PaymentStatus::REFUNDED,
            ], true),
            PaymentStatus::FAILED, PaymentStatus::REFUNDED => $to === $from,
        };
    }
}
