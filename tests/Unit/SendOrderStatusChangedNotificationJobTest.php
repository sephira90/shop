<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\SendOrderStatusChangedNotificationJob;
use App\Models\Order;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendOrderStatusChangedNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_order_status_changed_notification_to_order_email(): void
    {
        Notification::fake();

        $order = $this->createOrder('notify-order@example.com');

        (new SendOrderStatusChangedNotificationJob(
            orderId: $order->id,
            previousStatus: 'processing',
            currentStatus: 'shipped',
            source: 'shipping_webhook',
            correlationId: 'cid-notify-success',
        ))->handle();

        Notification::assertSentOnDemand(
            OrderStatusChangedNotification::class,
            static function (
                OrderStatusChangedNotification $notification,
                array $channels,
                object $notifiable,
            ): bool {
                $payload = $notification->toArray($notifiable);

                return in_array('mail', $channels, true)
                    && $notifiable instanceof AnonymousNotifiable
                    && $notifiable->routeNotificationFor('mail') === 'notify-order@example.com'
                    && $payload['previous_status'] === 'processing'
                    && $payload['current_status'] === 'shipped';
            },
        );
    }

    public function test_handle_skips_when_order_missing(): void
    {
        Notification::fake();

        (new SendOrderStatusChangedNotificationJob(
            orderId: 'missing-order',
            previousStatus: 'processing',
            currentStatus: 'shipped',
            source: 'shipping_webhook',
            correlationId: 'cid-notify-missing',
        ))->handle();

        Notification::assertNothingSent();
    }

    private function createOrder(string $email): Order
    {
        return Order::unguarded(fn (): Order => Order::query()->create([
            'order_number' => 'ORD-STATUS-NOTIFY-TEST-001',
            'email' => $email,
            'status' => 'processing',
            'payment_status' => 'captured',
            'shipment_status' => 'packed',
            'currency' => 'USD',
            'subtotal' => 100,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100,
            'billing_address' => ['line1' => '1 Main Street'],
            'shipping_address' => ['line1' => '1 Main Street'],
            'cart_snapshot' => ['items' => []],
            'placed_at' => now(),
        ]));
    }
}
