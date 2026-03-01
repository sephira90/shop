<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\DispatchShipmentJob;

class QueueOrderSideEffects
{
    /**
     * Handle order placed event.
     */
    public function handle(OrderPlaced $event): void
    {
        if ($event->order->hasCapturedPayment()) {
            DispatchShipmentJob::dispatch($event->order->id)->afterCommit();
        }
    }
}
