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

    'snapshot' => [
        'default_source' => (string) env('OBSERVABILITY_SNAPSHOT_SOURCE', 'runtime'),
    ],

    'alerts' => [
        // Run periodic SLO checks through app:observability-report.
        'enabled' => (bool) env('APP_OBSERVABILITY_ALERTS_ENABLED', true),
        'cron' => (string) env('APP_OBSERVABILITY_ALERTS_CRON', '*/30 * * * *'),
        'minutes' => (int) env('APP_OBSERVABILITY_ALERTS_WINDOW_MINUTES', 120),
        'source' => (string) env('APP_OBSERVABILITY_ALERTS_SOURCE', 'runtime'),
        'max_api_slow_rate' => (float) env('APP_OBSERVABILITY_ALERTS_MAX_API_SLOW_RATE', 0.30),
        'max_webhook_lag_warn_rate' => (float) env('APP_OBSERVABILITY_ALERTS_MAX_WEBHOOK_LAG_WARN_RATE', 0.30),
        'require_api_samples' => (bool) env('APP_OBSERVABILITY_ALERTS_REQUIRE_API_SAMPLES', true),
        'require_webhook_samples' => (bool) env('APP_OBSERVABILITY_ALERTS_REQUIRE_WEBHOOK_SAMPLES', true),
        'cooldown_minutes' => (int) env('APP_OBSERVABILITY_ALERTS_COOLDOWN_MINUTES', 30),
        'email' => [
            'enabled' => (bool) env('APP_OBSERVABILITY_ALERTS_EMAIL_ENABLED', false),
            'recipients' => array_values(array_filter(array_map(
                static fn (string $recipient): string => trim($recipient),
                explode(',', (string) env('APP_OBSERVABILITY_ALERTS_EMAIL_RECIPIENTS', '')),
            ))),
        ],
        'slack' => [
            'enabled' => (bool) env('APP_OBSERVABILITY_ALERTS_SLACK_ENABLED', false),
            'webhook_url' => (string) env('APP_OBSERVABILITY_ALERTS_SLACK_WEBHOOK_URL', ''),
        ],
        'pagerduty' => [
            'enabled' => (bool) env('APP_OBSERVABILITY_ALERTS_PAGERDUTY_ENABLED', false),
            'integration_key' => (string) env('APP_OBSERVABILITY_ALERTS_PAGERDUTY_INTEGRATION_KEY', ''),
            'severity' => (string) env('APP_OBSERVABILITY_ALERTS_PAGERDUTY_SEVERITY', 'warning'),
        ],
    ],
];
