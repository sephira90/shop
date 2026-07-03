<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create job instance.
     */
    public function __construct(
        public readonly string $orderId,
        public readonly string $correlationId,
    ) {}

    /**
     * Execute queued job.
     */
    public function handle(): void
    {
        Log::withContext(['correlation_id' => $this->correlationId]);

        $order = Order::query()->find($this->orderId);

        if (! $order instanceof Order) {
            return;
        }

        Log::info('Order confirmation queued.', [
            'orderId' => $order->id,
            'orderNumber' => $order->order_number,
        ]);
    }
}
