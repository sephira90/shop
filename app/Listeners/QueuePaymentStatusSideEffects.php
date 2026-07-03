<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\PaymentStatus;
use App\Events\PaymentStatusChanged;
use App\Jobs\DispatchShipmentJob;
use App\Jobs\SendOrderConfirmationJob;
use App\Support\Observability\CorrelationContext;
use Illuminate\Support\Facades\Log;

class QueuePaymentStatusSideEffects
{
    public function __construct(
        private readonly CorrelationContext $correlationContext,
    ) {}

    /**
     * Handle payment status transition side effects.
     */
    public function handle(PaymentStatusChanged $event): void
    {
        Log::info('Payment status transitioned.', [
            'orderId' => $event->orderId,
            'paymentId' => $event->paymentId,
            'previousStatus' => $event->previousStatus->value,
            'currentStatus' => $event->currentStatus->value,
            'source' => $event->source->value,
        ]);

        if (
            $event->currentStatus !== PaymentStatus::CAPTURED
            || $event->previousStatus === PaymentStatus::CAPTURED
        ) {
            return;
        }

        $correlationId = $this->correlationContext->currentOrNew();

        SendOrderConfirmationJob::dispatch($event->orderId, $correlationId)->afterCommit();
        DispatchShipmentJob::dispatch($event->orderId, $correlationId)->afterCommit();
    }
}
