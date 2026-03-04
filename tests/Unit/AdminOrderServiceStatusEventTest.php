<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Admin\Orders\Dto\UpdateAdminOrderStatusInputDto;
use App\Domain\Exceptions\OrderTransitionException;
use App\Domain\Order\StatusTransitionSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Services\Admin\AdminOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminOrderServiceStatusEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_status_dispatches_order_status_changed_event_when_status_changes(): void
    {
        Event::fake();

        $order = $this->createPendingOrder();

        app(AdminOrderService::class)->updateStatus(
            $order,
            new UpdateAdminOrderStatusInputDto(
                status: null,
                paymentStatus: PaymentStatus::CAPTURED,
                shipmentStatus: null,
            ),
        );

        Event::assertDispatched(
            OrderStatusChanged::class,
            fn (OrderStatusChanged $event): bool => $event->orderId === $order->id
                && $event->previousStatus === OrderStatus::PENDING
                && $event->currentStatus === OrderStatus::PAID
                && $event->source === StatusTransitionSource::ADMIN_ORDER_UPDATE,
        );
    }

    public function test_update_status_does_not_dispatch_order_status_changed_event_when_status_remains_same(): void
    {
        Event::fake();

        $order = $this->createPendingOrder();

        app(AdminOrderService::class)->updateStatus(
            $order,
            new UpdateAdminOrderStatusInputDto(
                status: OrderStatus::PENDING,
                paymentStatus: null,
                shipmentStatus: null,
            ),
        );

        Event::assertNotDispatched(OrderStatusChanged::class);
    }

    public function test_update_status_rejects_direct_invalid_admin_transition(): void
    {
        Event::fake();

        $order = $this->createPendingOrder();
        $order->update([
            'status' => OrderStatus::CANCELLED->value,
        ]);

        $this->expectException(OrderTransitionException::class);
        $this->expectExceptionMessage('Order status transition is not allowed.');

        try {
            app(AdminOrderService::class)->updateStatus(
                $order->refresh(),
                new UpdateAdminOrderStatusInputDto(
                    status: OrderStatus::PAID,
                    paymentStatus: null,
                    shipmentStatus: null,
                ),
            );
        } finally {
            Event::assertNotDispatched(OrderStatusChanged::class);
        }
    }

    private function createPendingOrder(): Order
    {
        return Order::query()->create([
            'order_number' => 'ORD-ADMIN-EVENT-TEST-001',
            'email' => 'admin-status@example.com',
            'status' => OrderStatus::PENDING->value,
            'payment_status' => PaymentStatus::PENDING->value,
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
    }
}
