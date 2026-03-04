<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Domain\ValueObjects\Money;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Webhook\WebhookProcessingPipeline;
use App\Support\Data\JsonPayload;
use App\Support\Data\TypedValue;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class PaymentService
{
    /**
     * Create payment service.
     */
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private WebhookProcessingPipeline $webhookProcessingPipeline,
        private PaymentWebhookAdapter $paymentWebhookAdapter,
    ) {}

    /**
     * Initiate payment for order.
     */
    public function initiate(Order $order, string $idempotencyKey): Payment
    {
        $gatewayDriver = $this->gatewayDriver();

        return DB::transaction(function () use ($order, $idempotencyKey, $gatewayDriver): Payment {
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

            $orderTotal = $this->resolveOrderTotal($lockedOrder);
            $result = $this->gateway->createPayment($lockedOrder, $orderTotal, $idempotencyKey);

            return Payment::query()->create([
                'order_id' => $lockedOrder->id,
                'idempotency_key' => $idempotencyKey,
                'gateway' => $gatewayDriver,
                'transaction_id' => $result->transactionId,
                'amount' => $orderTotal->toFloat(),
                'currency' => $lockedOrder->currency,
                'status' => $result->status->value,
                'payload' => $result->payload->toArray(),
            ]);
        });
    }

    /**
     * Process webhook payload in idempotent way.
     */
    public function processWebhook(
        JsonPayload $payload,
        string $signature,
        ?string $receivedAtIso8601 = null,
        string $source = 'runtime',
        ?string $prevalidatedEventId = null,
    ): void {
        $this->webhookProcessingPipeline->process(
            $this->paymentWebhookAdapter,
            $payload,
            $signature,
            $receivedAtIso8601,
            $source,
            $prevalidatedEventId,
        );
    }

    private function gatewayDriver(): string
    {
        return TypedValue::string(config('payment.driver', 'fake-payment'));
    }

    private function resolveOrderTotal(Order $order): Money
    {
        $currency = (string) $order->currency;
        $rawTotal = $order->getRawOriginal('total');

        if (is_string($rawTotal) || is_int($rawTotal) || is_float($rawTotal)) {
            return Money::fromDecimal($rawTotal, $currency);
        }

        return Money::fromDecimal((float) $order->total, $currency);
    }
}
