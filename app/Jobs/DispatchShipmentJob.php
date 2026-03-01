<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Services\Shipping\ShippingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchShipmentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create job instance.
     */
    public function __construct(public readonly string $orderId) {}

    /**
     * Execute queued job.
     */
    public function handle(ShippingService $shippingService): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order instanceof Order) {
            return;
        }

        if (! $order->hasCapturedPayment()) {
            return;
        }

        $shippingService->createShipment($order);
    }
}
