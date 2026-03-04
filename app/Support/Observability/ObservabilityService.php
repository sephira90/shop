<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Data\TypedValue;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

final class ObservabilityService
{
    private const SOURCE_RUNTIME = 'runtime';

    private const SOURCE_SMOKE = 'smoke';

    public function __construct(
        private readonly ObservabilityMetricStore $observabilityMetricStore,
        private readonly ObservabilitySnapshotBuilder $observabilitySnapshotBuilder,
    ) {}

    /**
     * Report API request latency sample.
     */
    public function apiRequest(
        string $method,
        string $path,
        int $status,
        float $durationMs,
        string $source = self::SOURCE_RUNTIME,
    ): void {
        if (! $this->enabled()) {
            return;
        }

        $normalizedSource = $this->normalizeSource($source);
        $payload = [
            'metric' => 'api.request',
            'source' => $normalizedSource,
            'method' => strtoupper($method),
            'path' => $path,
            'status' => $status,
            'duration_ms' => round($durationMs, 2),
        ];

        $this->logger()->info('observability.api_request', $payload);

        if ($normalizedSource === self::SOURCE_RUNTIME && $durationMs >= $this->apiSlowThresholdMs()) {
            $this->logger()->warning('observability.api_request_slow', $payload);
        }

        $this->observabilityMetricStore->storeApiSample($durationMs, $normalizedSource, $this->apiSlowThresholdMs());
    }

    /**
     * Report catalog cache hit ratio sample.
     */
    public function catalogCache(
        string $segment,
        bool $hit,
        float $durationMs,
        ?int $items = null,
        string $source = self::SOURCE_RUNTIME,
    ): void {
        if (! $this->enabled()) {
            return;
        }

        $normalizedSource = $this->normalizeSource($source);
        $payload = [
            'metric' => 'catalog.cache',
            'source' => $normalizedSource,
            'segment' => $segment,
            'cache_hit' => $hit,
            'duration_ms' => round($durationMs, 2),
            'items' => $items,
        ];

        $this->logger()->info('observability.catalog_cache', $payload);

        if ($normalizedSource === self::SOURCE_RUNTIME && ! $hit && $durationMs >= $this->catalogSlowThresholdMs()) {
            $this->logger()->warning('observability.catalog_cache_slow_miss', $payload);
        }

        $this->observabilityMetricStore->storeCatalogSample(
            $segment,
            $hit,
            $durationMs,
            $normalizedSource,
            $this->catalogSlowThresholdMs(),
        );
    }

    /**
     * Report webhook pipeline and lag sample.
     */
    public function webhook(
        string $provider,
        string $eventId,
        string $outcome,
        float $durationMs,
        ?float $lagMs,
        string $source = self::SOURCE_RUNTIME,
    ): void {
        if (! $this->enabled()) {
            return;
        }

        $normalizedSource = $this->normalizeSource($source);
        $payload = [
            'metric' => 'webhook.processing',
            'source' => $normalizedSource,
            'provider' => $provider,
            'event_id' => $eventId,
            'outcome' => $outcome,
            'duration_ms' => round($durationMs, 2),
            'lag_ms' => $lagMs !== null ? round($lagMs, 2) : null,
        ];

        $this->logger()->info('observability.webhook', $payload);

        if ($normalizedSource === self::SOURCE_RUNTIME && $durationMs >= $this->webhookSlowThresholdMs()) {
            $this->logger()->warning('observability.webhook_slow', $payload);
        }

        if ($normalizedSource === self::SOURCE_RUNTIME && $lagMs !== null && $lagMs >= $this->webhookLagWarnThresholdMs()) {
            $this->logger()->warning('observability.webhook_lag', $payload);
        }

        $this->observabilityMetricStore->storeWebhookSample(
            $provider,
            $outcome,
            $durationMs,
            $lagMs,
            $normalizedSource,
            $this->webhookLagWarnThresholdMs(),
        );
    }

    /**
     * Report domain status transition sample.
     */
    public function statusTransition(
        string $domain,
        string $aggregateId,
        string $previousStatus,
        string $currentStatus,
        string $source = self::SOURCE_RUNTIME,
    ): void {
        if (! $this->enabled()) {
            return;
        }

        $normalizedSource = $this->normalizeSource($source);
        $payload = [
            'metric' => 'domain.status_transition',
            'source' => $normalizedSource,
            'transition_source' => $source,
            'domain' => $domain,
            'aggregate_id' => $aggregateId,
            'previous_status' => $previousStatus,
            'current_status' => $currentStatus,
        ];

        $this->logger()->info('observability.status_transition', $payload);

        $this->observabilityMetricStore->storeStatusTransitionSample(
            domain: $domain,
            previousStatus: $previousStatus,
            currentStatus: $currentStatus,
            source: $normalizedSource,
        );
    }

    /**
     * Build aggregated observability snapshot from rolling cache counters.
     *
     * @return array{
     *     minutes:int,
     *     source:string,
     *     api:array{count:int,avg_duration_ms:float,slow_count:int},
     *     catalog:list<array{
     *         segment:string,
     *         count:int,
     *         hit_count:int,
     *         miss_count:int,
     *         hit_ratio:float,
     *         avg_duration_ms:float,
     *         slow_miss_count:int
     *     }>,
     *     webhook:list<array{
     *         provider:string,
     *         count:int,
     *         processed_count:int,
     *         duplicate_count:int,
     *         rejected_count:int,
     *         avg_duration_ms:float,
     *         avg_lag_ms:?float,
     *         lag_warn_count:int
     *     }>
     * }
     */
    public function snapshot(int $minutes = 60, string $source = self::SOURCE_RUNTIME): array
    {
        $normalizedSource = $this->normalizeSource($source);

        return $this->observabilitySnapshotBuilder->build($minutes, $normalizedSource);
    }

    /**
     * Normalize metric source key.
     */
    private function normalizeSource(string $source): string
    {
        $normalized = strtolower(str_replace([':', ' '], ['_', '_'], trim($source)));

        return in_array($normalized, [self::SOURCE_RUNTIME, self::SOURCE_SMOKE], true)
            ? $normalized
            : self::SOURCE_RUNTIME;
    }

    /**
     * Check if observability hooks are enabled.
     */
    private function enabled(): bool
    {
        return (bool) config('observability.enabled', true);
    }

    /**
     * Resolve logger channel.
     */
    private function logger(): LoggerInterface
    {
        return Log::channel(TypedValue::string(config('observability.channel', config('logging.default', 'stack'))));
    }

    /**
     * Resolve API slow threshold.
     */
    private function apiSlowThresholdMs(): int
    {
        return TypedValue::int(config('observability.api.slow_ms', 800));
    }

    /**
     * Resolve catalog slow threshold.
     */
    private function catalogSlowThresholdMs(): int
    {
        return TypedValue::int(config('observability.catalog.slow_ms', 400));
    }

    /**
     * Resolve webhook slow processing threshold.
     */
    private function webhookSlowThresholdMs(): int
    {
        return TypedValue::int(config('observability.webhook.slow_ms', 500));
    }

    /**
     * Resolve webhook lag warning threshold.
     */
    private function webhookLagWarnThresholdMs(): int
    {
        return TypedValue::int(config('observability.webhook.lag_warn_ms', 1500));
    }
}
