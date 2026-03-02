<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use App\Models\WebhookReceipt;
use App\Support\Data\JsonPayload;
use App\Support\Observability\ObservabilityService;
use Carbon\CarbonImmutable;
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
     */
    public function process(
        WebhookProcessorAdapterInterface $adapter,
        JsonPayload $payload,
        string $signature,
        ?string $receivedAtIso8601 = null,
        string $source = 'runtime',
        ?string $prevalidatedEventId = null,
    ): void {
        $startedAt = hrtime(true);
        $eventId = 'unknown';
        $outcome = WebhookProcessingOutcome::REJECTED;

        try {
            if ($prevalidatedEventId !== null) {
                $eventId = trim($prevalidatedEventId);
            } else {
                $eventId = $adapter->prevalidateIngress($payload, $signature)->eventId;
            }

            if ($eventId === '') {
                throw WebhookIngressException::missingEventId();
            }

            $payloadHash = hash('sha256', json_encode($payload->toArray(), JSON_THROW_ON_ERROR));

            DB::transaction(function () use ($adapter, $payload, $eventId, $payloadHash, &$outcome): void {
                $provider = $adapter->receiptProvider();
                $now = now();

                DB::table('webhook_receipts')->insertOrIgnore([
                    'provider' => $provider,
                    'event_id' => $eventId,
                    'payload_hash' => $payloadHash,
                    'processed_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $receipt = WebhookReceipt::query()
                    ->where('provider', $provider)
                    ->where('event_id', $eventId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($receipt->payload_hash !== $payloadHash) {
                    throw WebhookIngressException::payloadHashMismatch();
                }

                if ($receipt->processed_at !== null) {
                    $outcome = WebhookProcessingOutcome::DUPLICATE;

                    return;
                }

                $outcome = $adapter->processTransition($payload);
                if ($outcome === WebhookProcessingOutcome::REJECTED) {
                    throw WebhookIngressException::rejectedTransition();
                }

                $receipt->update([
                    'processed_at' => $now,
                    'updated_at' => $now,
                ]);
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
