<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Order\OrderPaymentStatusResolver;
use App\Models\Order;
use App\Services\Shipping\ShippingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DispatchShipmentJob implements ShouldQueue
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
    public function handle(
        ShippingService $shippingService,
        OrderPaymentStatusResolver $orderPaymentStatusResolver,
    ): void {
        Log::withContext(['correlation_id' => $this->correlationId]);

        $order = Order::query()->find($this->orderId);

        if (! $order instanceof Order) {
            return;
        }

        if (! $orderPaymentStatusResolver->hasCapturedPayment($order)) {
            return;
        }

        $shippingService->createShipment($order);
    }
}
