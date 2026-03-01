<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Jobs\DispatchShipmentJob;
use App\Jobs\SendOrderConfirmationJob;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentWebhookIngressResolver;
use App\Services\Payment\PaymentWebhookTransitionApplier;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Support\Data\JsonPayload;
use App\Support\Data\TypedValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentWebhookTransitionApplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_captures_payment_updates_order_and_dispatches_post_payment_jobs(): void
    {
        Bus::fake();

        [$order, $payment] = $this->createOrderWithPayment(
            orderStatus: OrderStatus::PENDING,
            orderPaymentStatus: PaymentStatus::PENDING,
            paymentStatus: PaymentStatus::PENDING,
        );

        $resolvedPayload = app(PaymentWebhookIngressResolver::class)->resolve(JsonPayload::fromArray([
            'event_id' => 'evt-payment-captured',
            'transaction_id' => $payment->transaction_id,
            'status' => 'paid',
        ]));

        $outcome = DB::transaction(
            fn (): WebhookProcessingOutcome => app(PaymentWebhookTransitionApplier::class)->apply(
                $resolvedPayload,
                TypedValue::string(config('payment.driver', 'fake-payment')),
            ),
        );

        $freshOrder = $order->fresh();
        $freshPayment = $payment->fresh();

        $this->assertInstanceOf(Order::class, $freshOrder);
        $this->assertInstanceOf(Payment::class, $freshPayment);
        $this->assertSame(WebhookProcessingOutcome::PROCESSED, $outcome);
        $this->assertSame(PaymentStatus::CAPTURED, $freshPayment->status);
        $this->assertNotNull($freshPayment->processed_at);
        $paymentPayload = $freshPayment->getAttribute('payload');
        $this->assertIsArray($paymentPayload);
        $normalizedPaymentPayload = TypedValue::associativeArray($paymentPayload);
        $webhookPayload = TypedValue::associativeArray($normalizedPaymentPayload['webhook'] ?? []);
        $this->assertSame('evt-payment-captured', $webhookPayload['event_id'] ?? null);
        $this->assertSame(OrderStatus::PAID, $freshOrder->status);
        $this->assertSame(PaymentStatus::CAPTURED, $freshOrder->payment_status);

        Bus::assertDispatched(
            SendOrderConfirmationJob::class,
            fn (SendOrderConfirmationJob $job): bool => $job->orderId === $order->id,
        );
        Bus::assertDispatched(
            DispatchShipmentJob::class,
            fn (DispatchShipmentJob $job): bool => $job->orderId === $order->id,
        );
    }

    public function test_apply_returns_duplicate_for_payment_regression_and_keeps_state_stable(): void
    {
        Bus::fake();

        [$order, $payment] = $this->createOrderWithPayment(
            orderStatus: OrderStatus::PAID,
            orderPaymentStatus: PaymentStatus::CAPTURED,
            paymentStatus: PaymentStatus::CAPTURED,
        );

        $resolvedPayload = app(PaymentWebhookIngressResolver::class)->resolve(JsonPayload::fromArray([
            'event_id' => 'evt-payment-regression',
            'transaction_id' => $payment->transaction_id,
            'status' => 'failed',
        ]));

        $outcome = DB::transaction(
            fn (): WebhookProcessingOutcome => app(PaymentWebhookTransitionApplier::class)->apply(
                $resolvedPayload,
                TypedValue::string(config('payment.driver', 'fake-payment')),
            ),
        );

        $freshOrder = $order->fresh();
        $freshPayment = $payment->fresh();

        $this->assertInstanceOf(Order::class, $freshOrder);
        $this->assertInstanceOf(Payment::class, $freshPayment);
        $this->assertSame(WebhookProcessingOutcome::DUPLICATE, $outcome);
        $this->assertSame(PaymentStatus::CAPTURED, $freshPayment->status);
        $this->assertSame(OrderStatus::PAID, $freshOrder->status);
        $this->assertSame(PaymentStatus::CAPTURED, $freshOrder->payment_status);

        Bus::assertNothingDispatched();
    }

    /**
     * @return array{Order, Payment}
     */
    private function createOrderWithPayment(
        OrderStatus $orderStatus,
        PaymentStatus $orderPaymentStatus,
        PaymentStatus $paymentStatus,
    ): array {
        $order = Order::query()->create([
            'order_number' => 'ORD-PAYMENT-WEBHOOK-TEST',
            'email' => 'guest@example.com',
            'status' => $orderStatus->value,
            'payment_status' => $orderPaymentStatus->value,
            'shipment_status' => ShipmentStatus::PENDING->value,
            'currency' => 'USD',
            'subtotal' => 100,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100,
            'billing_address' => ['line1' => '1 Main Street'],
            'shipping_address' => ['line1' => '1 Main Street'],
            'cart_snapshot' => ['items' => []],
            'placed_at' => now(),
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'idempotency_key' => 'payment-webhook-test-key-'.$paymentStatus->value,
            'gateway' => TypedValue::string(config('payment.driver', 'fake-payment')),
            'transaction_id' => 'txn-payment-webhook-test-'.$paymentStatus->value,
            'amount' => 100,
            'currency' => 'USD',
            'status' => $paymentStatus->value,
            'payload' => ['provider' => 'fake'],
        ]);

        return [$order, $payment];
    }
}
