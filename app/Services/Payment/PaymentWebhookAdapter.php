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
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Services\Webhook\WebhookProcessorAdapterInterface;
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
    public function verifySignature(array $payload, string $signature): bool
    {
        return $this->gateway->verifyWebhookSignature($payload, $signature);
    }

    /**
     * {@inheritDoc}
     */
    public function extractEventId(array $payload): string
    {
        return $this->gateway->extractEventId($payload);
    }

    /**
     * Resolve payment transaction id from payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function extractTransactionId(array $payload): string
    {
        return $this->gateway->extractTransactionId($payload);
    }

    /**
     * {@inheritDoc}
     */
    public function processTransition(array $payload): WebhookProcessingOutcome
    {
        $transactionId = $this->extractTransactionId($payload);
        if ($transactionId === '') {
            throw new DomainException('Payment transaction id is required.');
        }

        $paymentStatus = $this->gateway->resolveWebhookStatus($payload);

        $payment = Payment::query()
            ->where('gateway', $this->receiptProvider())
            ->where('transaction_id', $transactionId)
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
            'payload' => array_merge($payment->payload ?? [], ['webhook' => $payload]),
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
