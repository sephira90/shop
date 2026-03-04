<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Domain\Order\StatusTransitionSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderStatusChanged;
use App\Events\PaymentStatusChanged;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderStatusTransitionPolicy;
use App\Services\Payment\Dto\PaymentWebhookPayloadDto;
use App\Services\Webhook\WebhookIngressException;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Support\Data\TypedValue;

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

        $previousPaymentStatus = PaymentStatus::from(TypedValue::string($payment->getRawOriginal('status')));

        if (! $this->paymentStatusTransitionPolicy->canTransition($previousPaymentStatus, $paymentStatus)) {
            return WebhookProcessingOutcome::DUPLICATE;
        }

        /** @var array<string, mixed> $existingPayload */
        $existingPayload = (array) $payment->getAttribute('payload');

        $payment->update([
            'status' => $paymentStatus->value,
            'payload' => array_merge($existingPayload, ['webhook' => $webhookPayload->rawPayload->toArray()]),
            'processed_at' => now(),
        ]);

        $order = Order::query()
            ->whereKey($payment->order_id)
            ->lockForUpdate()
            ->first();

        if (! $order instanceof Order) {
            throw WebhookIngressException::paymentOrderNotFound();
        }

        $previousOrderStatus = OrderStatus::from(TypedValue::string($order->getRawOriginal('status')));
        $orderStatus = $this->orderStatusTransitionPolicy->resolveByPaymentStatus($previousOrderStatus, $paymentStatus);

        $order->update([
            'payment_status' => $paymentStatus->value,
            'status' => $orderStatus->value,
        ]);

        if ($orderStatus !== $previousOrderStatus) {
            event(new OrderStatusChanged(
                orderId: $order->id,
                previousStatus: $previousOrderStatus,
                currentStatus: $orderStatus,
                source: StatusTransitionSource::PAYMENT_WEBHOOK,
            ));
        }

        event(new PaymentStatusChanged(
            orderId: $order->id,
            paymentId: (string) $payment->id,
            previousStatus: $previousPaymentStatus,
            currentStatus: $paymentStatus,
            source: StatusTransitionSource::PAYMENT_WEBHOOK,
        ));

        return WebhookProcessingOutcome::PROCESSED;
    }
}
