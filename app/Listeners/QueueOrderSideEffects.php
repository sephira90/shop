<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Order\OrderPaymentStatusResolver;
use App\Events\OrderPlaced;
use App\Jobs\DispatchShipmentJob;
use App\Support\Observability\CorrelationContext;

class QueueOrderSideEffects
{
    public function __construct(
        private readonly OrderPaymentStatusResolver $orderPaymentStatusResolver,
        private readonly CorrelationContext $correlationContext,
    ) {}

    /**
     * Handle order placed event.
     */
    public function handle(OrderPlaced $event): void
    {
        if ($this->orderPaymentStatusResolver->hasCapturedPayment($event->order)) {
            DispatchShipmentJob::dispatch($event->order->id, $this->correlationContext->currentOrNew())
                ->afterCommit();
        }
    }
}
