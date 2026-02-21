<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Observability
    |--------------------------------------------------------------------------
    |
    | This configuration controls structured telemetry hooks used for
    | API latency, catalog cache hit ratio, and webhook processing lag.
    |
    */

    'enabled' => (bool) env('OBSERVABILITY_ENABLED', true),
    'channel' => (string) env('OBSERVABILITY_CHANNEL', 'observability'),

    'api' => [
        'slow_ms' => (int) env('OBSERVABILITY_API_SLOW_MS', 800),
    ],

    'catalog' => [
        'slow_ms' => (int) env('OBSERVABILITY_CATALOG_SLOW_MS', 400),
    ],

    'webhook' => [
        'slow_ms' => (int) env('OBSERVABILITY_WEBHOOK_SLOW_MS', 500),
        'lag_warn_ms' => (int) env('OBSERVABILITY_WEBHOOK_LAG_WARN_MS', 1500),
    ],
];
