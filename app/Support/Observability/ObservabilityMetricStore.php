<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Data\TypedValue;
use Illuminate\Support\Facades\Cache;

final class ObservabilityMetricStore
{
    private const CACHE_TTL_MINUTES = 1440;

    private const CATALOG_REGISTRY_KEY = 'observability:registry:catalog_segments';

    private const WEBHOOK_REGISTRY_KEY = 'observability:registry:webhook_providers';

    public function storeApiSample(float $durationMs, string $source, int $apiSlowThresholdMs): void
    {
        $bucket = $this->bucket();
        $this->incrementCounter($this->cacheKey('api', $source, 'count', $bucket), 1);
        $this->incrementCounter($this->cacheKey('api', $source, 'duration_ms_total', $bucket), $this->durationToInt($durationMs));

        if ($durationMs >= $apiSlowThresholdMs) {
            $this->incrementCounter($this->cacheKey('api', $source, 'slow_count', $bucket), 1);
        }
    }

    public function storeCatalogSample(string $segment, bool $hit, float $durationMs, string $source, int $catalogSlowThresholdMs): void
    {
        $normalizedSegment = $this->normalizeKeyPart($segment);
        $bucket = $this->bucket();

        $this->registerValue(self::CATALOG_REGISTRY_KEY, $segment);

        $this->incrementCounter($this->cacheKey('catalog', $source, $normalizedSegment, 'count', $bucket), 1);
        $this->incrementCounter($this->cacheKey('catalog', $source, $normalizedSegment, 'duration_ms_total', $bucket), $this->durationToInt($durationMs));

        if ($hit) {
            $this->incrementCounter($this->cacheKey('catalog', $source, $normalizedSegment, 'hit_count', $bucket), 1);

            return;
        }

        $this->incrementCounter($this->cacheKey('catalog', $source, $normalizedSegment, 'miss_count', $bucket), 1);

        if ($durationMs >= $catalogSlowThresholdMs) {
            $this->incrementCounter($this->cacheKey('catalog', $source, $normalizedSegment, 'slow_miss_count', $bucket), 1);
        }
    }

    public function storeWebhookSample(
        string $provider,
        string $outcome,
        float $durationMs,
        ?float $lagMs,
        string $source,
        int $webhookLagWarnThresholdMs,
    ): void {
        $normalizedProvider = $this->normalizeKeyPart($provider);
        $bucket = $this->bucket();

        $this->registerValue(self::WEBHOOK_REGISTRY_KEY, $provider);

        $this->incrementCounter($this->cacheKey('webhook', $source, $normalizedProvider, 'count', $bucket), 1);
        $this->incrementCounter($this->cacheKey('webhook', $source, $normalizedProvider, 'duration_ms_total', $bucket), $this->durationToInt($durationMs));
        $this->incrementCounter(
            $this->cacheKey('webhook', $source, $normalizedProvider, 'outcome', $this->normalizeKeyPart($outcome), $bucket),
            1,
        );

        if ($lagMs === null) {
            return;
        }

        $this->incrementCounter($this->cacheKey('webhook', $source, $normalizedProvider, 'lag_ms_total', $bucket), $this->durationToInt($lagMs));
        $this->incrementCounter($this->cacheKey('webhook', $source, $normalizedProvider, 'lag_samples', $bucket), 1);

        if ($lagMs >= $webhookLagWarnThresholdMs) {
            $this->incrementCounter($this->cacheKey('webhook', $source, $normalizedProvider, 'lag_warn_count', $bucket), 1);
        }
    }

    /**
     * @return array{count:int,duration_ms_total:int,slow_count:int}
     */
    public function apiMetrics(int $minutes, string $source): array
    {
        $buckets = $this->windowBuckets($minutes);

        return [
            'count' => $this->sumByBuckets($buckets, 'api', $source, 'count'),
            'duration_ms_total' => $this->sumByBuckets($buckets, 'api', $source, 'duration_ms_total'),
            'slow_count' => $this->sumByBuckets($buckets, 'api', $source, 'slow_count'),
        ];
    }

    /**
     * @return list<array{
     *     segment:string,
     *     count:int,
     *     hit_count:int,
     *     miss_count:int,
     *     duration_ms_total:int,
     *     slow_miss_count:int
     * }>
     */
    public function catalogMetrics(int $minutes, string $source): array
    {
        $segments = Cache::get(self::CATALOG_REGISTRY_KEY, []);
        if (! is_array($segments)) {
            return [];
        }

        sort($segments);
        $buckets = $this->windowBuckets($minutes);
        $rows = [];

        foreach ($segments as $segmentValue) {
            if (! is_string($segmentValue) || $segmentValue === '') {
                continue;
            }

            $normalizedSegment = $this->normalizeKeyPart($segmentValue);
            $count = $this->sumByBuckets($buckets, 'catalog', $source, $normalizedSegment, 'count');

            if ($count === 0) {
                continue;
            }

            $rows[] = [
                'segment' => $segmentValue,
                'count' => $count,
                'hit_count' => $this->sumByBuckets($buckets, 'catalog', $source, $normalizedSegment, 'hit_count'),
                'miss_count' => $this->sumByBuckets($buckets, 'catalog', $source, $normalizedSegment, 'miss_count'),
                'duration_ms_total' => $this->sumByBuckets($buckets, 'catalog', $source, $normalizedSegment, 'duration_ms_total'),
                'slow_miss_count' => $this->sumByBuckets($buckets, 'catalog', $source, $normalizedSegment, 'slow_miss_count'),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{
     *     provider:string,
     *     count:int,
     *     processed_count:int,
     *     duplicate_count:int,
     *     rejected_count:int,
     *     duration_ms_total:int,
     *     lag_ms_total:int,
     *     lag_samples:int,
     *     lag_warn_count:int
     * }>
     */
    public function webhookMetrics(int $minutes, string $source): array
    {
        $providers = Cache::get(self::WEBHOOK_REGISTRY_KEY, []);
        if (! is_array($providers)) {
            return [];
        }

        sort($providers);
        $buckets = $this->windowBuckets($minutes);
        $rows = [];

        foreach ($providers as $providerValue) {
            if (! is_string($providerValue) || $providerValue === '') {
                continue;
            }

            $normalizedProvider = $this->normalizeKeyPart($providerValue);
            $count = $this->sumByBuckets($buckets, 'webhook', $source, $normalizedProvider, 'count');

            if ($count === 0) {
                continue;
            }

            $rows[] = [
                'provider' => $providerValue,
                'count' => $count,
                'processed_count' => $this->sumByBuckets($buckets, 'webhook', $source, $normalizedProvider, 'outcome', 'processed'),
                'duplicate_count' => $this->sumByBuckets($buckets, 'webhook', $source, $normalizedProvider, 'outcome', 'duplicate'),
                'rejected_count' => $this->sumByBuckets($buckets, 'webhook', $source, $normalizedProvider, 'outcome', 'rejected'),
                'duration_ms_total' => $this->sumByBuckets($buckets, 'webhook', $source, $normalizedProvider, 'duration_ms_total'),
                'lag_ms_total' => $this->sumByBuckets($buckets, 'webhook', $source, $normalizedProvider, 'lag_ms_total'),
                'lag_samples' => $this->sumByBuckets($buckets, 'webhook', $source, $normalizedProvider, 'lag_samples'),
                'lag_warn_count' => $this->sumByBuckets($buckets, 'webhook', $source, $normalizedProvider, 'lag_warn_count'),
            ];
        }

        return $rows;
    }

    /**
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
     * @param  list<string>  $buckets
     */
    private function sumByBuckets(array $buckets, string ...$parts): int
    {
        $sum = 0;

        foreach ($buckets as $bucket) {
            $sum += TypedValue::int(Cache::get($this->cacheKey(...array_merge($parts, [$bucket])), 0));
        }

        return $sum;
    }

    private function incrementCounter(string $key, int $value): void
    {
        if ($value <= 0) {
            return;
        }

        $expiresAt = now()->addMinutes(self::CACHE_TTL_MINUTES);
        Cache::add($key, 0, $expiresAt);
        Cache::increment($key, $value);
        Cache::put($key, TypedValue::int(Cache::get($key, 0)), $expiresAt);
    }

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

    private function cacheKey(string ...$parts): string
    {
        return 'observability:'.implode(':', $parts);
    }

    private function bucket(): string
    {
        return now()->format('YmdHi');
    }

    private function normalizeKeyPart(string $value): string
    {
        return strtolower(str_replace([':', ' '], ['_', '_'], trim($value)));
    }

    private function durationToInt(float $durationMs): int
    {
        return max(0, (int) round($durationMs));
    }
}
