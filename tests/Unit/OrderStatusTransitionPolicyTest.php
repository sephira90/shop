<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Services\Order\OrderStatusTransitionPolicy;
use PHPUnit\Framework\TestCase;

class OrderStatusTransitionPolicyTest extends TestCase
{
    /**
     * Ensure order transition matrix is deterministic.
     */
    public function test_order_status_transition_matrix_is_stable(): void
    {
        $policy = new OrderStatusTransitionPolicy;

        $allowedTransitions = [
            OrderStatus::PENDING->value => [
                OrderStatus::PENDING,
                OrderStatus::PAID,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELLED,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::PAID->value => [
                OrderStatus::PAID,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELLED,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::PROCESSING->value => [
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELLED,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::SHIPPED->value => [
                OrderStatus::SHIPPED,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELLED,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::COMPLETED->value => [
                OrderStatus::COMPLETED,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::CANCELLED->value => [
                OrderStatus::CANCELLED,
                OrderStatus::PROCESSING,
                OrderStatus::REFUNDED,
            ],
            OrderStatus::REFUNDED->value => [
                OrderStatus::REFUNDED,
            ],
        ];

        foreach (OrderStatus::cases() as $from) {
            foreach (OrderStatus::cases() as $to) {
                $expected = in_array($to, $allowedTransitions[$from->value], true);

                self::assertSame(
                    $expected,
                    $policy->canTransition($from, $to),
                    sprintf('Unexpected order transition "%s" -> "%s".', $from->value, $to->value),
                );

                self::assertSame(
                    $expected,
                    $policy->canTransitionDirectly($from, $to),
                    sprintf('Unexpected direct order transition "%s" -> "%s".', $from->value, $to->value),
                );
            }
        }

        self::assertTrue($policy->canTransition('paid', 'cancelled'));
        self::assertFalse($policy->canTransition('completed', 'pending'));
        self::assertFalse($policy->canTransitionDirectly('cancelled', 'paid'));
    }

    /**
     * Ensure order status transitions from payment events are deterministic.
     */
    public function test_order_status_resolution_by_payment_status_is_stable(): void
    {
        $policy = new OrderStatusTransitionPolicy;

        $cases = [
            [OrderStatus::PENDING, PaymentStatus::CAPTURED, OrderStatus::PAID],
            [OrderStatus::PENDING, PaymentStatus::FAILED, OrderStatus::CANCELLED],
            [OrderStatus::PENDING, PaymentStatus::REFUNDED, OrderStatus::REFUNDED],
            [OrderStatus::PAID, PaymentStatus::CAPTURED, OrderStatus::PAID],
            [OrderStatus::PAID, PaymentStatus::FAILED, OrderStatus::PAID],
            [OrderStatus::CANCELLED, PaymentStatus::FAILED, OrderStatus::CANCELLED],
            [OrderStatus::CANCELLED, PaymentStatus::REFUNDED, OrderStatus::REFUNDED],
            ['pending', PaymentStatus::CAPTURED, OrderStatus::PAID],
        ];

        foreach ($cases as [$currentStatus, $paymentStatus, $expectedStatus]) {
            self::assertSame(
                $expectedStatus,
                $policy->resolveByPaymentStatus($currentStatus, $paymentStatus),
                sprintf(
                    'Unexpected order transition from payment "%s" + "%s".',
                    is_string($currentStatus) ? $currentStatus : $currentStatus->value,
                    $paymentStatus->value,
                ),
            );
        }
    }

    /**
     * Ensure order status transitions from shipment events are deterministic.
     */
    public function test_order_status_resolution_by_shipment_status_is_stable(): void
    {
        $policy = new OrderStatusTransitionPolicy;

        $cases = [
            [OrderStatus::PENDING, ShipmentStatus::SHIPPED, OrderStatus::PENDING],
            [OrderStatus::PAID, ShipmentStatus::DELIVERED, OrderStatus::COMPLETED],
            [OrderStatus::CANCELLED, ShipmentStatus::DELIVERED, OrderStatus::CANCELLED],
            [OrderStatus::COMPLETED, ShipmentStatus::RETURNED, OrderStatus::COMPLETED],
            ['paid', ShipmentStatus::DELIVERED, OrderStatus::COMPLETED],
        ];

        foreach ($cases as [$currentStatus, $shipmentStatus, $expectedStatus]) {
            self::assertSame(
                $expectedStatus,
                $policy->resolveByShipmentStatus($currentStatus, $shipmentStatus),
                sprintf(
                    'Unexpected order transition from shipment "%s" + "%s".',
                    is_string($currentStatus) ? $currentStatus : $currentStatus->value,
                    $shipmentStatus->value,
                ),
            );
        }
    }
}
