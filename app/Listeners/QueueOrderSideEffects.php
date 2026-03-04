<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Order\OrderPaymentStatusResolver;
use App\Events\OrderPlaced;
use App\Jobs\DispatchShipmentJob;

class QueueOrderSideEffects
{
    public function __construct(
        private readonly OrderPaymentStatusResolver $orderPaymentStatusResolver,
    ) {}

    /**
     * Handle order placed event.
     */
    public function handle(OrderPlaced $event): void
    {
        if ($this->orderPaymentStatusResolver->hasCapturedPayment($event->order)) {
            DispatchShipmentJob::dispatch($event->order->id)->afterCommit();
        }
    }
}
