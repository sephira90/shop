<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use Illuminate\Support\Facades\Log;

class LogOrderStatusTransition
{
    /**
     * Handle order status transition logging.
     */
    public function handle(OrderStatusChanged $event): void
    {
        Log::info('Order status transitioned.', [
            'orderId' => $event->orderId,
            'previousStatus' => $event->previousStatus->value,
            'currentStatus' => $event->currentStatus->value,
            'source' => $event->source->value,
        ]);
    }
}
