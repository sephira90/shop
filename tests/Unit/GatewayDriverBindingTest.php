<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\ShippingGatewayInterface;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Infrastructure\Payments\FakePaymentGateway;
use App\Infrastructure\Shipping\FakeShippingGateway;
use App\Models\Order;
use App\Services\Payment\Dto\PaymentCreationResultDto;
use App\Services\Shipping\Dto\ShipmentCreationResultDto;
use App\Support\Data\JsonPayload;
use InvalidArgumentException;
use Tests\TestCase;

final class GatewayDriverBindingTest extends TestCase
{
    public function test_default_gateway_drivers_resolve_to_fake_gateways(): void
    {
        $this->assertInstanceOf(FakePaymentGateway::class, $this->app->make(PaymentGatewayInterface::class));
        $this->assertInstanceOf(FakeShippingGateway::class, $this->app->make(ShippingGatewayInterface::class));
    }

    public function test_it_resolves_gateways_from_configured_driver_map(): void
    {
        config()->set('payment.drivers.stub-payment', TestPaymentGateway::class);
        config()->set('shipping.drivers.stub-shipping', TestShippingGateway::class);
        config()->set('payment.driver', 'stub-payment');
        config()->set('shipping.driver', 'stub-shipping');

        $this->assertInstanceOf(TestPaymentGateway::class, $this->app->make(PaymentGatewayInterface::class));
        $this->assertInstanceOf(TestShippingGateway::class, $this->app->make(ShippingGatewayInterface::class));
    }

    public function test_it_throws_for_unknown_payment_driver(): void
    {
        config()->set('payment.driver', 'missing-payment-driver');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment driver [missing-payment-driver].');

        $this->app->make(PaymentGatewayInterface::class);
    }

    public function test_it_throws_for_unknown_shipping_driver(): void
    {
        config()->set('shipping.driver', 'missing-shipping-driver');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported shipping driver [missing-shipping-driver].');

        $this->app->make(ShippingGatewayInterface::class);
    }
}

final class TestPaymentGateway implements PaymentGatewayInterface
{
    public function createPayment(Order $order, string $idempotencyKey): PaymentCreationResultDto
    {
        return new PaymentCreationResultDto(
            transactionId: 'test_txn_1',
            status: PaymentStatus::PENDING,
            payload: JsonPayload::fromArray([]),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function extractEventId(array $payload): string
    {
        return (string) ($payload['event_id'] ?? 'test-payment-event');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function extractTransactionId(array $payload): string
    {
        return (string) ($payload['transaction_id'] ?? 'test_txn_1');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolveWebhookStatus(array $payload): PaymentStatus
    {
        return PaymentStatus::PENDING;
    }
}

final class TestShippingGateway implements ShippingGatewayInterface
{
    public function createShipment(Order $order): ShipmentCreationResultDto
    {
        return new ShipmentCreationResultDto(
            trackingNumber: 'TRKTEST123456',
            status: ShipmentStatus::PENDING,
            cost: 0.0,
            payload: JsonPayload::fromArray([]),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function extractEventId(array $payload): string
    {
        return (string) ($payload['event_id'] ?? 'test-shipping-event');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function extractTrackingNumber(array $payload): string
    {
        return (string) ($payload['tracking_number'] ?? 'TRKTEST123456');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolveWebhookStatus(array $payload): ShipmentStatus
    {
        return ShipmentStatus::PENDING;
    }
}
