<?php

declare(strict_types=1);

namespace App\Support\Observability;

use Illuminate\Support\Facades\Cache;

final class ObservabilityAlertCooldownStore
{
    private const CACHE_KEY = 'observability:alerts:cooldown:last_failure_at';

    public function isSuppressed(): bool
    {
        $cooldownMinutes = max(0, (int) config('observability.alerts.cooldown_minutes', 30));

        return $cooldownMinutes > 0 && Cache::has(self::CACHE_KEY);
    }

    public function remember(): void
    {
        $cooldownMinutes = max(0, (int) config('observability.alerts.cooldown_minutes', 30));
        if ($cooldownMinutes <= 0) {
            return;
        }

        Cache::put(self::CACHE_KEY, now()->timestamp, now()->addMinutes($cooldownMinutes));
    }
}
