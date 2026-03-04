<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Support\Observability\ObservabilityService;

final readonly class RecordShipmentStatusTransitionMetric
{
    public function __construct(
        private ObservabilityService $observabilityService,
    ) {}

    public function handle(ShipmentStatusChanged $event): void
    {
        $this->observabilityService->statusTransition(
            domain: 'shipment',
            aggregateId: $event->shipmentId,
            previousStatus: $event->previousStatus->value,
            currentStatus: $event->currentStatus->value,
            source: $event->source->value,
        );
    }
}
