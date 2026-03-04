<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentStatusChanged;
use App\Support\Observability\ObservabilityService;

final readonly class RecordPaymentStatusTransitionMetric
{
    public function __construct(
        private ObservabilityService $observabilityService,
    ) {}

    public function handle(PaymentStatusChanged $event): void
    {
        $this->observabilityService->statusTransition(
            domain: 'payment',
            aggregateId: $event->paymentId,
            previousStatus: $event->previousStatus->value,
            currentStatus: $event->currentStatus->value,
            source: $event->source->value,
        );
    }
}
