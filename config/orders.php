<?php

declare(strict_types=1);

use App\Enums\OrderStatus;

$resolvePositiveInt = static function (string $envKey, int $default, int $maximum): int {
    $rawValue = env($envKey, $default);
    $candidate = $rawValue === '' ? $default : $rawValue;
    $value = filter_var(
        $candidate,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => $maximum]],
    );

    if ($value === false) {
        throw new InvalidArgumentException(sprintf('%s must be an integer between 1 and %d.', $envKey, $maximum));
    }

    return $value;
};

return [

    /*
    |--------------------------------------------------------------------------
    | Order Status Notifications
    |--------------------------------------------------------------------------
    |
    | Configure customer-facing order status milestones that should trigger
    | asynchronous email notifications. Values must map to OrderStatus enum
    | entries and are validated during listener resolution.
    |
    */

    'status_notifications' => [
        'notifiable_statuses' => [
            OrderStatus::SHIPPED->value,
            OrderStatus::COMPLETED->value,
            OrderStatus::CANCELLED->value,
            OrderStatus::REFUNDED->value,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Lifecycle Reconciliation
    |--------------------------------------------------------------------------
    |
    | The app:orders-reconcile command surfaces silent side-effect loss
    | windows that occur when a queued job fails to dispatch after commit
    | or exhausts its retries. Three independent detectors run; each has its
    | own bounded positive-integer window so operational tuning is explicit
    | and a misconfiguration fails fast at config resolution time.
    |
    | - stuck_shipment_minutes: paid orders whose shipment has not advanced
    |   beyond PENDING for at least this many minutes are reported.
    | - stale_pending_payment_minutes: orders whose payment is still PENDING
    |   for at least this many minutes are reported.
    | - failed_jobs_threshold: a non-empty queue.failed_jobs table with at
    |   least this many entries is reported (default 1).
    |
    */

    'reconciliation' => [
        'enabled' => (bool) env('APP_ORDERS_RECONCILE_ENABLED', true),
        'cron' => (string) env('APP_ORDERS_RECONCILE_CRON', '*/15 * * * *'),
        'stuck_shipment_minutes' => $resolvePositiveInt(
            envKey: 'ORDERS_RECONCILE_STUCK_SHIPMENT_MINUTES',
            default: 90,
            maximum: 43200, // 30 days
        ),
        'stale_pending_payment_minutes' => $resolvePositiveInt(
            envKey: 'ORDERS_RECONCILE_STALE_PENDING_PAYMENT_MINUTES',
            default: 60,
            maximum: 43200, // 30 days
        ),
        'failed_jobs_threshold' => $resolvePositiveInt(
            envKey: 'ORDERS_RECONCILE_FAILED_JOBS_THRESHOLD',
            default: 1,
            maximum: 100000,
        ),
    ],
];
