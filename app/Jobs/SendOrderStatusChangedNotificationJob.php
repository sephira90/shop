<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendOrderStatusChangedNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create job instance.
     */
    public function __construct(
        public readonly string $orderId,
        public readonly string $previousStatus,
        public readonly string $currentStatus,
        public readonly string $source,
    ) {}

    /**
     * Execute queued job.
     */
    public function handle(): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order instanceof Order) {
            return;
        }

        Notification::route('mail', $order->email)->notify(new OrderStatusChangedNotification(
            orderNumber: $order->order_number,
            previousStatus: $this->previousStatus,
            currentStatus: $this->currentStatus,
        ));

        Log::info('Order status change notification queued.', [
            'orderId' => $order->id,
            'orderNumber' => $order->order_number,
            'previousStatus' => $this->previousStatus,
            'currentStatus' => $this->currentStatus,
            'source' => $this->source,
        ]);
    }
}
