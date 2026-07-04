<?php

declare(strict_types=1);

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
    | Checkout Idempotency Retention
    |--------------------------------------------------------------------------
    |
    | Idempotency keys are short-lived records that protect the place-order
    | flow against duplicate submissions. Two independent windows exist:
    |
    | - pending_minutes: how long an unresolved (no order yet) idempotency
    |   record is retained before it can be replaced by a fresh attempt.
    | - completed_hours: how long a finalized idempotency record keeps
    |   serving the original order back to replays of the same request.
    |
    | Both values are validated as positive bounded integers at config
    | resolution time so a misconfiguration fails fast instead of producing
    | silent retention drift. Operational tuning is expected; do not raise
    | them beyond the cleanup retention window for idempotency records.
    |
    */

    'idempotency' => [
        'pending_minutes' => $resolvePositiveInt(
            envKey: 'CHECKOUT_IDEMPOTENCY_PENDING_MINUTES',
            default: 30,
            maximum: 10080, // 7 days
        ),
        'completed_hours' => $resolvePositiveInt(
            envKey: 'CHECKOUT_IDEMPOTENCY_COMPLETED_HOURS',
            default: 24,
            maximum: 720, // 30 days
        ),
    ],
];
