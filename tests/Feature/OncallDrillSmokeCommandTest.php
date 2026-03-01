<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Observability\ObservabilityService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OncallDrillSmokeCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure on-call drill command passes for dry-run checks when samples exist.
     */
    public function test_oncall_drill_smoke_passes_for_core_dry_run_checks(): void
    {
        $this->configureObservabilityForDrill();

        $observability = app(ObservabilityService::class);
        $observability->apiRequest('GET', '/api/v1/catalog/products', 200, 20.0);
        $observability->webhook('payment', 'evt-oncall-pass', 'processed', 15.0, 120.0);

        $this->artisanCommand('app:oncall-drill-smoke')
            ->assertSuccessful()
            ->expectsOutputToContain('oncall_healthcheck')
            ->expectsOutputToContain('oncall_observability_slo_report')
            ->expectsOutputToContain('oncall_cleanup_dry_run')
            ->expectsOutputToContain('On-call drill passed.');
    }

    /**
     * Ensure on-call drill command fails and prints escalation guidance when SLO report fails.
     */
    public function test_oncall_drill_smoke_fails_when_required_samples_are_missing(): void
    {
        $this->configureObservabilityForDrill();

        $this->artisanCommand('app:oncall-drill-smoke')
            ->assertFailed()
            ->expectsOutputToContain('oncall_observability_slo_report');
    }

    /**
     * Ensure on-call drill command is registered in scheduler.
     */
    public function test_oncall_drill_command_is_registered_in_scheduler(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $event = collect($schedule->events())
            ->first(static fn ($scheduledEvent) => str_contains((string) ($scheduledEvent->command ?? ''), 'app:oncall-drill-smoke'));

        $this->assertNotNull($event);
    }

    /**
     * Ensure on-call drill can execute write smoke checks when explicitly requested.
     */
    public function test_oncall_drill_smoke_runs_write_smokes_when_requested(): void
    {
        $this->configureObservabilityForDrill();

        $observability = app(ObservabilityService::class);
        $observability->apiRequest('GET', '/api/v1/catalog/products', 200, 20.0);
        $observability->webhook('payment', 'evt-oncall-write-pass', 'processed', 15.0, 120.0);

        $this->artisanCommand('app:oncall-drill-smoke --with-write-smokes')
            ->assertSuccessful()
            ->expectsOutputToContain('oncall_api_contract_smoke')
            ->expectsOutputToContain('oncall_webhook_flow_smoke')
            ->expectsOutputToContain('On-call drill passed.');
    }

    /**
     * Configure deterministic observability options for drill command tests.
     */
    private function configureObservabilityForDrill(): void
    {
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'null');
        config()->set('observability.alerts.minutes', 60);
        config()->set('observability.alerts.source', 'runtime');
        config()->set('observability.alerts.max_api_slow_rate', 0.30);
        config()->set('observability.alerts.max_webhook_lag_warn_rate', 0.30);
        config()->set('observability.alerts.require_api_samples', true);
        config()->set('observability.alerts.require_webhook_samples', true);

        Cache::flush();
    }
}
