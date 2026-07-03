<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Payment\PaymentService;
use App\Support\Data\JsonPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhookJob implements ShouldQueue
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
        public readonly string $correlationId,
    ) {}

    /**
     * Execute queued job.
     */
    public function handle(PaymentService $paymentService): void
    {
        Log::withContext(['correlation_id' => $this->correlationId]);

        $paymentService->processWebhook(
            JsonPayload::fromArray($this->payload),
            $this->signature,
            $this->receivedAtIso8601,
            prevalidatedEventId: $this->eventId,
            correlationId: $this->correlationId,
        );
    }
}
