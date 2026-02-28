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
use App\Services\Webhook\WebhookIngressException;
use App\Services\Webhook\WebhookProcessingOutcome;

final readonly class PaymentWebhookTransitionApplier
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private PaymentStatusTransitionPolicy $paymentStatusTransitionPolicy,
        private OrderStatusTransitionPolicy $orderStatusTransitionPolicy,
    ) {}

    public function apply(PaymentWebhookPayloadDto $webhookPayload, string $provider): WebhookProcessingOutcome
    {
        $paymentStatus = $this->gateway->resolveWebhookStatus($webhookPayload->rawPayload->toArray());

        $payment = Payment::query()
            ->where('gateway', $provider)
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
}
