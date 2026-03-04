<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Models\Order;
use ReflectionClass;
use Tests\TestCase;

class OrderModelBusinessLogicBoundaryTest extends TestCase
{
    public function test_order_model_does_not_keep_payment_status_business_logic_helpers(): void
    {
        $reflection = new ReflectionClass(Order::class);

        $this->assertFalse(
            $reflection->hasMethod('hasCapturedPayment'),
            'Order model must not contain hasCapturedPayment business helper; use domain service.'
        );
        $this->assertFalse(
            $reflection->hasMethod('normalizedPaymentStatus'),
            'Order model must not contain payment-status normalization helper; use domain service.'
        );
    }
}
