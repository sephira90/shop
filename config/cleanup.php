<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cleanup Lifecycle
    |--------------------------------------------------------------------------
    |
    | Maintenance cleanup removes stale idempotency receipts, webhook receipts,
    | and carts according to retention windows defined below.
    |
    */

    'enabled' => (bool) env('APP_CLEANUP_ENABLED', true),

    'schedule' => [
        'cron' => (string) env('APP_CLEANUP_CRON', '17 * * * *'),
    ],

    'retention' => [
        // Keep checkout idempotency rows this many hours after expiry timestamp.
        'idempotency_hours' => (int) env('APP_CLEANUP_IDEMPOTENCY_RETAIN_HOURS', 168),

        // Keep webhook receipts this many hours after processed/created timestamp.
        'webhook_hours' => (int) env('APP_CLEANUP_WEBHOOK_RETAIN_HOURS', 720),

        // Keep active carts this many hours since last update.
        'active_cart_hours' => (int) env('APP_CLEANUP_ACTIVE_CART_RETAIN_HOURS', 720),

        // Keep checked_out/abandoned carts this many hours since last update.
        'inactive_cart_hours' => (int) env('APP_CLEANUP_INACTIVE_CART_RETAIN_HOURS', 168),
    ],
];
