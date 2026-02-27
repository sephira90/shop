<?php

declare(strict_types=1);

namespace App\Application\Webhook\Commands;

use App\Jobs\ProcessPaymentWebhookJob;
use App\Services\Payment\PaymentWebhookAdapter;

final readonly class EnqueuePaymentWebhookHandler
{
    /**
     * Create payment webhook enqueue handler.
     */
    public function __construct(
        private PaymentWebhookAdapter $paymentWebhookAdapter,
    ) {}

    /**
     * Validate payment webhook payload and enqueue async processing job.
     */
    public function handle(EnqueuePaymentWebhookCommand $command): void
    {
        $metadata = $this->paymentWebhookAdapter->prevalidateIngress($command->payload, $command->signature);

        ProcessPaymentWebhookJob::dispatch(
            $command->payload->toArray(),
            $command->signature,
            $command->receivedAtIso8601,
            $metadata->eventId,
        );
    }
}
