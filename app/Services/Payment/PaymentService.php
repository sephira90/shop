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
use App\Models\WebhookReceipt;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class PaymentService
{
    /**
     * Create payment service.
     */
    public function __construct(private PaymentGatewayInterface $gateway) {}

    /**
     * Initiate payment for order.
     */
    public function initiate(Order $order, string $idempotencyKey): Payment
    {
        return DB::transaction(function () use ($order, $idempotencyKey): Payment {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder instanceof Order) {
                throw new DomainException('Order not found.');
            }

            $existingActivePayment = Payment::query()
                ->where('order_id', $lockedOrder->id)
                ->whereIn('status', [
                    PaymentStatus::PENDING->value,
                    PaymentStatus::AUTHORIZED->value,
                    PaymentStatus::CAPTURED->value,
                ])
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existingActivePayment instanceof Payment) {
                return $existingActivePayment;
            }

            $existingPayment = Payment::query()
                ->where('order_id', $lockedOrder->id)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existingPayment instanceof Payment) {
                return $existingPayment;
            }

            $result = $this->gateway->createPayment($lockedOrder, $idempotencyKey);

            return Payment::query()->create([
                'order_id' => $lockedOrder->id,
                'idempotency_key' => $idempotencyKey,
                'gateway' => 'fake-payment',
                'transaction_id' => $result['transaction_id'],
                'amount' => $lockedOrder->total,
                'currency' => $lockedOrder->currency,
                'status' => $result['status']->value,
                'payload' => $result['payload'],
            ]);
        });
    }

    /**
     * Process webhook payload in idempotent way.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processWebhook(array $payload, string $signature): void
    {
        if (! $this->gateway->verifyWebhookSignature($payload, $signature)) {
            throw new DomainException('Invalid webhook signature.');
        }

        $eventId = $this->gateway->extractEventId($payload);

        if ($eventId === '') {
            throw new DomainException('Webhook event id is required.');
        }

        $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        DB::transaction(function () use ($payload, $eventId, $payloadHash): void {
            $receipt = WebhookReceipt::query()->firstOrCreate(
                ['provider' => 'fake-payment', 'event_id' => $eventId],
                [
                    'payload_hash' => $payloadHash,
                    'processed_at' => null,
                ],
            );

            $receipt = WebhookReceipt::query()
                ->whereKey($receipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($receipt->payload_hash !== $payloadHash) {
                throw new DomainException('Webhook payload hash mismatch.');
            }

            if ($receipt->processed_at !== null) {
                return;
            }

            $transactionId = $this->gateway->extractTransactionId($payload);
            if ($transactionId === '') {
                throw new DomainException('Payment transaction id is required.');
            }
            $paymentStatus = $this->gateway->resolveWebhookStatus($payload);

            $payment = Payment::query()
                ->where('gateway', 'fake-payment')
                ->where('transaction_id', $transactionId)
                ->lockForUpdate()
                ->first();

            if (! $payment instanceof Payment) {
                throw new DomainException('Payment transaction not found.');
            }

            $previousPaymentStatus = PaymentStatus::from((string) $payment->getRawOriginal('status'));

            if (! $this->shouldApplyPaymentStatusTransition($previousPaymentStatus, $paymentStatus)) {
                $receipt->update(['processed_at' => now()]);

                return;
            }

            $payment->update([
                'status' => $paymentStatus->value,
                'payload' => array_merge($payment->payload ?? [], ['webhook' => $payload]),
                'processed_at' => now(),
            ]);

            $order = $payment->order;
            if ($order === null) {
                throw new DomainException('Payment order not found.');
            }

            $orderStatus = $this->resolveOrderStatus($order->status, $paymentStatus);

            $order->update([
                'payment_status' => $paymentStatus->value,
                'status' => $orderStatus->value,
            ]);

            if ($paymentStatus === PaymentStatus::CAPTURED && $previousPaymentStatus !== PaymentStatus::CAPTURED) {
                SendOrderConfirmationJob::dispatch($order->id);
                DispatchShipmentJob::dispatch($order->id);
            }

            $receipt->update(['processed_at' => now()]);
        });
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
