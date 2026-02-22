<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | On-call Drill
    |--------------------------------------------------------------------------
    |
    | Scheduled tabletop drill checks for checkout/webhook incident readiness.
    | By default the drill runs dry-run checks only.
    |
    */

    'drill' => [
        'enabled' => (bool) env('APP_ONCALL_DRILL_ENABLED', true),
        'cron' => (string) env('APP_ONCALL_DRILL_CRON', '45 3 * * *'),
        'with_write_smokes' => (bool) env('APP_ONCALL_DRILL_WITH_WRITE_SMOKES', false),
        'persist' => (bool) env('APP_ONCALL_DRILL_PERSIST', false),
    ],
];
