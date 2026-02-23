<?php

declare(strict_types=1);

namespace App\Support\Observability;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

final class ObservabilityService
{
    private const CACHE_TTL_MINUTES = 1440;

    private const SOURCE_RUNTIME = 'runtime';

    private const SOURCE_SMOKE = 'smoke';

    private const CATALOG_REGISTRY_KEY = 'observability:registry:catalog_segments';

    private const WEBHOOK_REGISTRY_KEY = 'observability:registry:webhook_providers';

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

        $this->storeApiSample($durationMs, $normalizedSource);
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

        $this->storeCatalogSample($segment, $hit, $durationMs, $normalizedSource);
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

        $this->storeWebhookSample($provider, $outcome, $durationMs, $lagMs, $normalizedSource);
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
        $window = max(1, min(1440, $minutes));
        $normalizedSource = $this->normalizeSource($source);
        $buckets = $this->windowBuckets($window);

        $apiCount = $this->sumByBuckets($buckets, 'api', $normalizedSource, 'count');
        $apiDurationTotal = $this->sumByBuckets($buckets, 'api', $normalizedSource, 'duration_ms_total');
        $apiSlowCount = $this->sumByBuckets($buckets, 'api', $normalizedSource, 'slow_count');

        $catalog = $this->catalogSnapshot($buckets, $normalizedSource);
        $webhook = $this->webhookSnapshot($buckets, $normalizedSource);

        return [
            'minutes' => $window,
            'source' => $normalizedSource,
            'api' => [
                'count' => $apiCount,
                'avg_duration_ms' => $apiCount > 0 ? round($apiDurationTotal / $apiCount, 2) : 0.0,
                'slow_count' => $apiSlowCount,
            ],
            'catalog' => $catalog,
            'webhook' => $webhook,
        ];
    }

    /**
     * Store one API sample in rolling counters.
     */
    private function storeApiSample(float $durationMs, string $source): void
    {
        $bucket = $this->bucket();
        $this->incrementCounter($this->cacheKey('api', $source, 'count', $bucket), 1);
        $this->incrementCounter($this->cacheKey('api', $source, 'duration_ms_total', $bucket), $this->durationToInt($durationMs));

        if ($durationMs >= $this->apiSlowThresholdMs()) {
            $this->incrementCounter($this->cacheKey('api', $source, 'slow_count', $bucket), 1);
        }
    }

    /**
     * Store one catalog sample in rolling counters.
     */
    private function storeCatalogSample(string $segment, bool $hit, float $durationMs, string $source): void
    {
        $normalizedSegment = $this->normalizeKeyPart($segment);
        $bucket = $this->bucket();

        $this->registerValue(self::CATALOG_REGISTRY_KEY, $segment);

        $this->incrementCounter($this->cacheKey('catalog', $source, $normalizedSegment, 'count', $bucket), 1);
        $this->incrementCounter($this->cacheKey('catalog', $source, $normalizedSegment, 'duration_ms_total', $bucket), $this->durationToInt($durationMs));

        if ($hit) {
            $this->incrementCounter($this->cacheKey('catalog', $source, $normalizedSegment, 'hit_count', $bucket), 1);
        } else {
            $this->incrementCounter($this->cacheKey('catalog', $source, $normalizedSegment, 'miss_count', $bucket), 1);

            if ($durationMs >= $this->catalogSlowThresholdMs()) {
                $this->incrementCounter($this->cacheKey('catalog', $source, $normalizedSegment, 'slow_miss_count', $bucket), 1);
            }
        }
    }

    /**
     * Store one webhook sample in rolling counters.
     */
    private function storeWebhookSample(
        string $provider,
        string $outcome,
        float $durationMs,
        ?float $lagMs,
        string $source,
    ): void {
        $normalizedProvider = $this->normalizeKeyPart($provider);
        $bucket = $this->bucket();

        $this->registerValue(self::WEBHOOK_REGISTRY_KEY, $provider);

        $this->incrementCounter($this->cacheKey('webhook', $source, $normalizedProvider, 'count', $bucket), 1);
        $this->incrementCounter($this->cacheKey('webhook', $source, $normalizedProvider, 'duration_ms_total', $bucket), $this->durationToInt($durationMs));

        $normalizedOutcome = $this->normalizeKeyPart($outcome);
        $this->incrementCounter($this->cacheKey('webhook', $source, $normalizedProvider, 'outcome', $normalizedOutcome, $bucket), 1);

        if ($lagMs !== null) {
            $this->incrementCounter($this->cacheKey('webhook', $source, $normalizedProvider, 'lag_ms_total', $bucket), $this->durationToInt($lagMs));
            $this->incrementCounter($this->cacheKey('webhook', $source, $normalizedProvider, 'lag_samples', $bucket), 1);

            if ($lagMs >= $this->webhookLagWarnThresholdMs()) {
                $this->incrementCounter($this->cacheKey('webhook', $source, $normalizedProvider, 'lag_warn_count', $bucket), 1);
            }
        }
    }

    /**
     * Build catalog snapshot payload.
     *
     * @param  list<string>  $buckets
     * @return list<array{
     *     segment:string,
     *     count:int,
     *     hit_count:int,
     *     miss_count:int,
     *     hit_ratio:float,
     *     avg_duration_ms:float,
     *     slow_miss_count:int
     * }>
     */
    private function catalogSnapshot(array $buckets, string $source): array
    {
        $segments = Cache::get(self::CATALOG_REGISTRY_KEY, []);
        if (! is_array($segments)) {
            return [];
        }

        sort($segments);
        $rows = [];

        foreach ($segments as $segmentValue) {
            if (! is_string($segmentValue) || $segmentValue === '') {
                continue;
            }

            $segment = $this->normalizeKeyPart($segmentValue);
            $count = $this->sumByBuckets($buckets, 'catalog', $source, $segment, 'count');

            if ($count === 0) {
                continue;
            }

            $hitCount = $this->sumByBuckets($buckets, 'catalog', $source, $segment, 'hit_count');
            $missCount = $this->sumByBuckets($buckets, 'catalog', $source, $segment, 'miss_count');
            $durationTotal = $this->sumByBuckets($buckets, 'catalog', $source, $segment, 'duration_ms_total');
            $slowMissCount = $this->sumByBuckets($buckets, 'catalog', $source, $segment, 'slow_miss_count');

            $rows[] = [
                'segment' => $segmentValue,
                'count' => $count,
                'hit_count' => $hitCount,
                'miss_count' => $missCount,
                'hit_ratio' => round($hitCount / $count, 4),
                'avg_duration_ms' => round($durationTotal / $count, 2),
                'slow_miss_count' => $slowMissCount,
            ];
        }

        return $rows;
    }

    /**
     * Build webhook snapshot payload.
     *
     * @param  list<string>  $buckets
     * @return list<array{
     *     provider:string,
     *     count:int,
     *     processed_count:int,
     *     duplicate_count:int,
     *     rejected_count:int,
     *     avg_duration_ms:float,
     *     avg_lag_ms:?float,
     *     lag_warn_count:int
     * }>
     */
    private function webhookSnapshot(array $buckets, string $source): array
    {
        $providers = Cache::get(self::WEBHOOK_REGISTRY_KEY, []);
        if (! is_array($providers)) {
            return [];
        }

        sort($providers);
        $rows = [];

        foreach ($providers as $providerValue) {
            if (! is_string($providerValue) || $providerValue === '') {
                continue;
            }

            $provider = $this->normalizeKeyPart($providerValue);
            $count = $this->sumByBuckets($buckets, 'webhook', $source, $provider, 'count');

            if ($count === 0) {
                continue;
            }

            $processedCount = $this->sumByBuckets($buckets, 'webhook', $source, $provider, 'outcome', 'processed');
            $duplicateCount = $this->sumByBuckets($buckets, 'webhook', $source, $provider, 'outcome', 'duplicate');
            $rejectedCount = $this->sumByBuckets($buckets, 'webhook', $source, $provider, 'outcome', 'rejected');
            $durationTotal = $this->sumByBuckets($buckets, 'webhook', $source, $provider, 'duration_ms_total');
            $lagTotal = $this->sumByBuckets($buckets, 'webhook', $source, $provider, 'lag_ms_total');
            $lagSamples = $this->sumByBuckets($buckets, 'webhook', $source, $provider, 'lag_samples');
            $lagWarnCount = $this->sumByBuckets($buckets, 'webhook', $source, $provider, 'lag_warn_count');

            $rows[] = [
                'provider' => $providerValue,
                'count' => $count,
                'processed_count' => $processedCount,
                'duplicate_count' => $duplicateCount,
                'rejected_count' => $rejectedCount,
                'avg_duration_ms' => round($durationTotal / $count, 2),
                'avg_lag_ms' => $lagSamples > 0 ? round($lagTotal / $lagSamples, 2) : null,
                'lag_warn_count' => $lagWarnCount,
            ];
        }

        return $rows;
    }

    /**
     * Build rolling minute buckets list.
     *
     * @return list<string>
     */
    private function windowBuckets(int $minutes): array
    {
        $buckets = [];

        foreach (range(0, $minutes - 1) as $offset) {
            $buckets[] = now()->subMinutes($offset)->format('YmdHi');
        }

        return $buckets;
    }

    /**
     * Sum one metric across buckets.
     *
     * @param  list<string>  $buckets
     */
    private function sumByBuckets(array $buckets, string ...$parts): int
    {
        $sum = 0;

        foreach ($buckets as $bucket) {
            $key = $this->cacheKey(...array_merge($parts, [$bucket]));
            $sum += (int) Cache::get($key, 0);
        }

        return $sum;
    }

    /**
     * Increment cache counter with expiration.
     */
    private function incrementCounter(string $key, int $value): void
    {
        if ($value <= 0) {
            return;
        }

        $expiresAt = now()->addMinutes(self::CACHE_TTL_MINUTES);
        Cache::add($key, 0, $expiresAt);
        Cache::increment($key, $value);
        Cache::put($key, (int) Cache::get($key, 0), $expiresAt);
    }

    /**
     * Register key dimension value.
     */
    private function registerValue(string $registryKey, string $value): void
    {
        $values = Cache::get($registryKey, []);
        if (! is_array($values)) {
            $values = [];
        }

        if (in_array($value, $values, true)) {
            return;
        }

        $values[] = $value;
        sort($values);
        Cache::put($registryKey, $values, now()->addMinutes(self::CACHE_TTL_MINUTES));
    }

    /**
     * Build cache key.
     */
    private function cacheKey(string ...$parts): string
    {
        return 'observability:'.implode(':', $parts);
    }

    /**
     * Resolve current minute bucket key.
     */
    private function bucket(): string
    {
        return now()->format('YmdHi');
    }

    /**
     * Normalize dynamic key part.
     */
    private function normalizeKeyPart(string $value): string
    {
        return strtolower(str_replace([':', ' '], ['_', '_'], trim($value)));
    }

    /**
     * Normalize metric source key.
     */
    private function normalizeSource(string $source): string
    {
        $normalized = $this->normalizeKeyPart($source);

        return in_array($normalized, [self::SOURCE_RUNTIME, self::SOURCE_SMOKE], true)
            ? $normalized
            : self::SOURCE_RUNTIME;
    }

    /**
     * Convert duration to integer milliseconds.
     */
    private function durationToInt(float $durationMs): int
    {
        return max(0, (int) round($durationMs));
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
        return Log::channel((string) config('observability.channel', config('logging.default', 'stack')));
    }

    /**
     * Resolve API slow threshold.
     */
    private function apiSlowThresholdMs(): int
    {
        return (int) config('observability.api.slow_ms', 800);
    }

    /**
     * Resolve catalog slow threshold.
     */
    private function catalogSlowThresholdMs(): int
    {
        return (int) config('observability.catalog.slow_ms', 400);
    }

    /**
     * Resolve webhook slow processing threshold.
     */
    private function webhookSlowThresholdMs(): int
    {
        return (int) config('observability.webhook.slow_ms', 500);
    }

    /**
     * Resolve webhook lag warning threshold.
     */
    private function webhookLagWarnThresholdMs(): int
    {
        return (int) config('observability.webhook.lag_warn_ms', 1500);
    }
}
