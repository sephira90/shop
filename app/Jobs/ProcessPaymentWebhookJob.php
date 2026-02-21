<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Payment\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    ) {}

    /**
     * Execute queued job.
     */
    public function handle(PaymentService $paymentService): void
    {
        $paymentService->processWebhook($this->payload, $this->signature, $this->receivedAtIso8601);
    }
}
