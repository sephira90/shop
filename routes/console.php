<?php

declare(strict_types=1);

use App\Support\Data\TypedValue;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:healthcheck')->everyFiveMinutes();
Schedule::command('app:maintenance-cleanup')
    ->cron(TypedValue::string(config('cleanup.schedule.cron', '17 * * * *')))
    ->withoutOverlapping()
    ->when(static fn (): bool => (bool) config('cleanup.enabled', true));

Schedule::command('app:observability-alert-check')
    ->cron(TypedValue::string(config('observability.alerts.cron', '*/30 * * * *')))
    ->withoutOverlapping()
    ->when(static fn (): bool => (bool) config('observability.alerts.enabled', true));

$oncallDrillParameters = [];

if ((bool) config('oncall.drill.with_write_smokes', false)) {
    $oncallDrillParameters['--with-write-smokes'] = true;
}

if ((bool) config('oncall.drill.persist', false)) {
    $oncallDrillParameters['--persist'] = true;
}

Schedule::command('app:oncall-drill-smoke', $oncallDrillParameters)
    ->cron(TypedValue::string(config('oncall.drill.cron', '45 3 * * *')))
    ->withoutOverlapping()
    ->when(static fn (): bool => (bool) config('oncall.drill.enabled', true));
