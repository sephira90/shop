<?php

declare(strict_types=1);

namespace App\Application\Webhook\Commands;

use App\Jobs\ProcessShippingWebhookJob;
use App\Services\Shipping\ShippingWebhookAdapter;

final readonly class EnqueueShippingWebhookHandler
{
    /**
     * Create shipping webhook enqueue handler.
     */
    public function __construct(
        private ShippingWebhookAdapter $shippingWebhookAdapter,
    ) {}

    /**
     * Validate shipping webhook payload and enqueue async processing job.
     */
    public function handle(EnqueueShippingWebhookCommand $command): void
    {
        $metadata = $this->shippingWebhookAdapter->prevalidateIngress($command->payload, $command->signature);

        ProcessShippingWebhookJob::dispatch(
            $command->payload->toArray(),
            $command->signature,
            $command->receivedAtIso8601,
            $metadata->eventId,
        );
    }
}
