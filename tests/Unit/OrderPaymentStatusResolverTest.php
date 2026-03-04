<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Order\OrderPaymentStatusResolver;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Tests\TestCase;

class OrderPaymentStatusResolverTest extends TestCase
{
    public function test_has_captured_payment_resolves_enum_and_string_values(): void
    {
        $resolver = app(OrderPaymentStatusResolver::class);

        $capturedOrder = new Order;
        $capturedOrder->setAttribute('payment_status', PaymentStatus::CAPTURED);

        $stringCapturedOrder = new Order;
        $stringCapturedOrder->setRawAttributes(['payment_status' => PaymentStatus::CAPTURED->value], true);

        $pendingOrder = new Order;
        $pendingOrder->setAttribute('payment_status', PaymentStatus::PENDING);

        $this->assertTrue($resolver->hasCapturedPayment($capturedOrder));
        $this->assertTrue($resolver->hasCapturedPayment($stringCapturedOrder));
        $this->assertFalse($resolver->hasCapturedPayment($pendingOrder));
    }

    public function test_normalize_payment_status_returns_null_for_unknown_or_missing_value(): void
    {
        $resolver = app(OrderPaymentStatusResolver::class);

        $orderWithInvalidStatus = new Order;
        $orderWithInvalidStatus->setRawAttributes(['payment_status' => 'unexpected'], true);

        $orderWithoutStatus = new Order;
        $orderWithoutStatus->setRawAttributes([], true);

        $this->assertNull($resolver->normalizePaymentStatus($orderWithInvalidStatus));
        $this->assertNull($resolver->normalizePaymentStatus($orderWithoutStatus));
    }
}
