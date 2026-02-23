<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Notifications\ObservabilitySloFailureNotification;
use App\Support\Observability\ObservabilityService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ObservabilityAlertCheckCommandTest extends TestCase
{
    /**
     * Ensure alert check routes notifications when observability report fails.
     */
    public function test_alert_check_routes_notifications_for_failed_slo_check(): void
    {
        $this->configureBaseObservability();
        $this->configureFailureRouting();

        Cache::flush();
        Notification::fake();
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(['ok' => true], 200),
            'https://events.pagerduty.com/v2/enqueue' => Http::response(['status' => 'success'], 202),
        ]);

        $this->artisan('app:observability-alert-check')
            ->assertFailed()
            ->expectsOutputToContain('Observability alerts sent via: email, slack, pagerduty.');

        Notification::assertSentOnDemandTimes(ObservabilitySloFailureNotification::class, 1);
        Notification::assertSentOnDemand(
            ObservabilitySloFailureNotification::class,
            static function (
                ObservabilitySloFailureNotification $notification,
                array $channels,
                object $notifiable,
            ): bool {
                return in_array('mail', $channels, true)
                    && $notifiable->routeNotificationFor('mail') === 'ops@example.com';
            },
        );

        Http::assertSentCount(2);
        Http::assertSent(static fn (Request $request): bool => str_starts_with($request->url(), 'https://hooks.slack.com/'));
        Http::assertSent(static fn (Request $request): bool => $request->url() === 'https://events.pagerduty.com/v2/enqueue');
    }

    /**
     * Ensure alert check does not route notifications when report passes.
     */
    public function test_alert_check_skips_notifications_when_report_passes(): void
    {
        $this->configureBaseObservability();
        $this->configureFailureRouting();

        Cache::flush();
        Notification::fake();
        Http::fake();

        $observability = app(ObservabilityService::class);
        $observability->apiRequest('GET', '/api/v1/catalog/products', 200, 20.0);
        $observability->webhook('payment', 'evt-ob-alert-success', 'processed', 15.0, 100.0);

        $this->artisan('app:observability-alert-check')
            ->assertSuccessful()
            ->expectsOutputToContain('Observability alert check passed.');

        Notification::assertNothingSent();
        Http::assertNothingSent();
    }

    /**
     * Ensure alert check uses configured source when evaluating SLO report.
     */
    public function test_alert_check_uses_configured_source_for_slo_report(): void
    {
        $this->configureBaseObservability();
        $this->configureFailureRouting();
        config()->set('observability.alerts.source', 'smoke');

        Cache::flush();
        Notification::fake();
        Http::fake();

        $observability = app(ObservabilityService::class);
        $observability->apiRequest('GET', '/api/v1/catalog/products', 200, 20.0, 'smoke');
        $observability->webhook('payment', 'evt-ob-alert-smoke', 'processed', 15.0, 100.0, 'smoke');

        $this->artisan('app:observability-alert-check')
            ->assertSuccessful()
            ->expectsOutputToContain('Observability alert check passed.');

        Notification::assertNothingSent();
        Http::assertNothingSent();
    }

    /**
     * Ensure cooldown suppresses duplicate alert dispatches.
     */
    public function test_alert_check_suppresses_duplicate_alerts_by_cooldown(): void
    {
        $this->configureBaseObservability();
        $this->configureFailureRouting();
        config()->set('observability.alerts.slack.enabled', false);
        config()->set('observability.alerts.pagerduty.enabled', false);
        config()->set('observability.alerts.cooldown_minutes', 60);

        Cache::flush();
        Notification::fake();

        $this->artisan('app:observability-alert-check')
            ->assertFailed()
            ->expectsOutputToContain('Observability alerts sent via: email.');

        $this->artisan('app:observability-alert-check')
            ->assertFailed()
            ->expectsOutputToContain('Observability alert routing suppressed by cooldown window.');

        Notification::assertSentOnDemandTimes(ObservabilitySloFailureNotification::class, 1);
    }

    /**
     * Configure base observability defaults for deterministic tests.
     */
    private function configureBaseObservability(): void
    {
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'null');
        config()->set('observability.api.slow_ms', 800);
        config()->set('observability.webhook.lag_warn_ms', 1500);
        config()->set('observability.alerts.minutes', 60);
        config()->set('observability.alerts.source', 'runtime');
        config()->set('observability.alerts.max_api_slow_rate', 0.30);
        config()->set('observability.alerts.max_webhook_lag_warn_rate', 0.30);
        config()->set('observability.alerts.require_api_samples', true);
        config()->set('observability.alerts.require_webhook_samples', true);
        config()->set('observability.alerts.cooldown_minutes', 0);
    }

    /**
     * Configure all routing channels for failure scenario.
     */
    private function configureFailureRouting(): void
    {
        config()->set('observability.alerts.email.enabled', true);
        config()->set('observability.alerts.email.recipients', ['ops@example.com']);
        config()->set('observability.alerts.slack.enabled', true);
        config()->set('observability.alerts.slack.webhook_url', 'https://hooks.slack.com/services/T000/B000/TEST');
        config()->set('observability.alerts.pagerduty.enabled', true);
        config()->set('observability.alerts.pagerduty.integration_key', 'pd_test_integration_key');
        config()->set('observability.alerts.pagerduty.severity', 'warning');
    }
}
