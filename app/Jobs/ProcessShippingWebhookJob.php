<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Shipping\ShippingService;
use App\Support\Data\JsonPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessShippingWebhookJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create job instance.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly array $payload,
        public readonly string $signature,
        public readonly string $receivedAtIso8601,
        public readonly string $eventId,
    ) {}

    /**
     * Execute queued job.
     */
    public function handle(ShippingService $shippingService): void
    {
        $shippingService->processWebhook(
            JsonPayload::fromArray($this->payload),
            $this->signature,
            $this->receivedAtIso8601,
            prevalidatedEventId: $this->eventId,
        );
    }
}
