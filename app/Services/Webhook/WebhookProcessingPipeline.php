<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use App\Models\WebhookReceipt;
use App\Support\Observability\ObservabilityService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class WebhookProcessingPipeline
{
    /**
     * Create webhook processing pipeline.
     */
    public function __construct(
        private ObservabilityService $observabilityService,
    ) {}

    /**
     * Execute unified webhook processing flow.
     *
     * @param  array<string, mixed>  $payload
     */
    public function process(
        WebhookProcessorAdapterInterface $adapter,
        array $payload,
        string $signature,
        ?string $receivedAtIso8601 = null,
        string $source = 'runtime',
    ): void {
        $startedAt = hrtime(true);
        $eventId = 'unknown';
        $outcome = WebhookProcessingOutcome::REJECTED;

        try {
            if (! $adapter->verifySignature($payload, $signature)) {
                throw new DomainException($adapter->invalidSignatureMessage());
            }

            $eventId = $adapter->extractEventId($payload);
            if ($eventId === '') {
                throw new DomainException('Webhook event id is required.');
            }

            $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

            DB::transaction(function () use ($adapter, $payload, $eventId, $payloadHash, &$outcome): void {
                $receipt = WebhookReceipt::query()->firstOrCreate(
                    ['provider' => $adapter->receiptProvider(), 'event_id' => $eventId],
                    [
                        'payload_hash' => $payloadHash,
                        'processed_at' => null,
                    ],
                );

                $receipt = WebhookReceipt::query()
                    ->whereKey($receipt->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($receipt->payload_hash !== $payloadHash) {
                    throw new DomainException('Webhook payload hash mismatch.');
                }

                if ($receipt->processed_at !== null) {
                    $outcome = WebhookProcessingOutcome::DUPLICATE;

                    return;
                }

                $outcome = $adapter->processTransition($payload);
                if ($outcome === WebhookProcessingOutcome::REJECTED) {
                    throw new DomainException('Webhook adapter rejected processing transition.');
                }

                $receipt->update(['processed_at' => now()]);
            });
        } finally {
            $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
            $lagMs = $this->resolveLagMs($receivedAtIso8601);

            $this->observabilityService->webhook(
                provider: $adapter->observabilityProvider(),
                eventId: $eventId,
                outcome: $outcome->value,
                durationMs: $durationMs,
                lagMs: $lagMs,
                source: $source,
            );
        }
    }

    /**
     * Resolve webhook lag in milliseconds from received timestamp.
     */
    private function resolveLagMs(?string $receivedAtIso8601): ?float
    {
        if ($receivedAtIso8601 === null || $receivedAtIso8601 === '') {
            return null;
        }

        try {
            $receivedAt = CarbonImmutable::parse($receivedAtIso8601);

            return (float) $receivedAt->diffInMilliseconds(now(), false);
        } catch (\Throwable) {
            return null;
        }
    }
}
