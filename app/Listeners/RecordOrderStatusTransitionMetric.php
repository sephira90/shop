<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Support\Observability\ObservabilityService;

final readonly class RecordOrderStatusTransitionMetric
{
    public function __construct(
        private ObservabilityService $observabilityService,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $this->observabilityService->statusTransition(
            domain: 'order',
            aggregateId: $event->orderId,
            previousStatus: $event->previousStatus->value,
            currentStatus: $event->currentStatus->value,
            source: $event->source->value,
        );
    }
}
