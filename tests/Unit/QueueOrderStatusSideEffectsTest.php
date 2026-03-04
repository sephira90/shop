<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Order\StatusTransitionSource;
use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Jobs\SendOrderStatusChangedNotificationJob;
use App\Listeners\QueueOrderStatusSideEffects;
use Illuminate\Support\Facades\Bus;
use InvalidArgumentException;
use Tests\TestCase;

class QueueOrderStatusSideEffectsTest extends TestCase
{
    public function test_handle_dispatches_notification_job_for_notifiable_statuses(): void
    {
        Bus::fake();

        config()->set('orders.status_notifications.notifiable_statuses', [
            OrderStatus::SHIPPED->value,
            OrderStatus::CANCELLED->value,
        ]);

        $listener = app(QueueOrderStatusSideEffects::class);

        $listener->handle(new OrderStatusChanged(
            orderId: 'order-shipped',
            previousStatus: OrderStatus::PROCESSING,
            currentStatus: OrderStatus::SHIPPED,
            source: StatusTransitionSource::SHIPPING_WEBHOOK,
        ));

        $listener->handle(new OrderStatusChanged(
            orderId: 'order-cancelled',
            previousStatus: OrderStatus::PAID,
            currentStatus: OrderStatus::CANCELLED,
            source: StatusTransitionSource::ADMIN_ORDER_UPDATE,
        ));

        Bus::assertDispatched(
            SendOrderStatusChangedNotificationJob::class,
            fn (SendOrderStatusChangedNotificationJob $job): bool => $job->orderId === 'order-shipped'
                && $job->previousStatus === OrderStatus::PROCESSING->value
                && $job->currentStatus === OrderStatus::SHIPPED->value
                && $job->source === 'shipping_webhook',
        );

        Bus::assertDispatched(
            SendOrderStatusChangedNotificationJob::class,
            fn (SendOrderStatusChangedNotificationJob $job): bool => $job->orderId === 'order-cancelled'
                && $job->previousStatus === OrderStatus::PAID->value
                && $job->currentStatus === OrderStatus::CANCELLED->value
                && $job->source === 'admin_order_update',
        );
    }

    public function test_handle_skips_notification_job_for_non_notifiable_statuses(): void
    {
        Bus::fake();

        config()->set('orders.status_notifications.notifiable_statuses', [
            OrderStatus::SHIPPED->value,
        ]);

        app(QueueOrderStatusSideEffects::class)->handle(new OrderStatusChanged(
            orderId: 'order-paid',
            previousStatus: OrderStatus::PENDING,
            currentStatus: OrderStatus::PAID,
            source: StatusTransitionSource::PAYMENT_WEBHOOK,
        ));

        Bus::assertNothingDispatched();
    }

    public function test_listener_resolution_fails_for_invalid_notifiable_status_configuration(): void
    {
        config()->set('orders.status_notifications.notifiable_statuses', ['invalid-status']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid orders.status_notifications.notifiable_statuses entry [invalid-status].',
        );

        app(QueueOrderStatusSideEffects::class);
    }
}
