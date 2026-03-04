<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use Illuminate\Support\Facades\Log;

class LogShipmentStatusTransition
{
    /**
     * Handle shipment status transition logging.
     */
    public function handle(ShipmentStatusChanged $event): void
    {
        Log::info('Shipment status transitioned.', [
            'orderId' => $event->orderId,
            'shipmentId' => $event->shipmentId,
            'previousStatus' => $event->previousStatus->value,
            'currentStatus' => $event->currentStatus->value,
            'source' => $event->source->value,
        ]);
    }
}
