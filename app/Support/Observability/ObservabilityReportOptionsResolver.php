<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Data\TypedValue;
use App\Support\Observability\Dto\ObservabilityReportOptionsDto;
use InvalidArgumentException;

final class ObservabilityReportOptionsResolver
{
    /**
     * @param  array{
     *     minutes:mixed,
     *     source:mixed,
     *     max_api_slow_rate:mixed,
     *     max_webhook_lag_warn_rate:mixed,
     *     require_api_samples:mixed,
     *     require_webhook_samples:mixed,
     *     json:mixed
     * }  $options
     */
    public function resolve(array $options): ObservabilityReportOptionsDto
    {
        $minutes = $this->resolveMinutes($options['minutes']);
        $source = $this->resolveSource($options['source']);

        return new ObservabilityReportOptionsDto(
            minutes: $minutes,
            source: $source,
            maxApiSlowRate: $this->resolveRateThreshold($options['max_api_slow_rate'], 'max-api-slow-rate'),
            maxWebhookLagWarnRate: $this->resolveRateThreshold($options['max_webhook_lag_warn_rate'], 'max-webhook-lag-warn-rate'),
            requireApiSamples: (bool) $options['require_api_samples'],
            requireWebhookSamples: (bool) $options['require_webhook_samples'],
            json: (bool) $options['json'],
        );
    }

    private function resolveMinutes(mixed $raw): int
    {
        if (! is_numeric($raw)) {
            throw new InvalidArgumentException('Option --minutes must be between 1 and 1440.');
        }

        $minutes = (int) $raw;

        if ($minutes < 1 || $minutes > 1440) {
            throw new InvalidArgumentException('Option --minutes must be between 1 and 1440.');
        }

        return $minutes;
    }

    private function resolveSource(mixed $raw): string
    {
        $source = TypedValue::nullableTrimmedString($raw) ?? '';

        if ($source === '') {
            $source = TypedValue::nullableTrimmedString(config('observability.snapshot.default_source', 'runtime')) ?? 'runtime';
        }

        $source = strtolower($source);

        if (! in_array($source, ['runtime', 'smoke'], true)) {
            throw new InvalidArgumentException('Option --source must be one of: runtime, smoke.');
        }

        return $source;
    }

    private function resolveRateThreshold(mixed $raw, string $name): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            throw new InvalidArgumentException(sprintf('Option --%s must be a number in [0..1].', $name));
        }

        $value = (float) $raw;

        if ($value < 0 || $value > 1) {
            throw new InvalidArgumentException(sprintf('Option --%s must be between 0 and 1.', $name));
        }

        return $value;
    }
}
