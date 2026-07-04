<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Observability\Channels;

use App\Support\Observability\AlertDeliveryOutcome;
use App\Support\Observability\Channels\PagerDutyObservabilityAlertChannel;
use App\Support\Observability\Dto\ObservabilityAlertMessageDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifies the PagerDuty channel surfaces an explicit outcome: disabled when
 * the flag is off, failed on missing integration key or non-2xx response,
 * and delivered on a successful Events API v2 trigger.
 */
class PagerDutyObservabilityAlertChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_returns_disabled_when_channel_flag_is_off(): void
    {
        config()->set('observability.alerts.pagerduty.enabled', false);
        config()->set('observability.alerts.pagerduty.integration_key', 'key');

        $outcome = app(PagerDutyObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::DISABLED, $outcome);
    }

    public function test_send_returns_failed_when_integration_key_is_empty(): void
    {
        config()->set('observability.alerts.pagerduty.enabled', true);
        config()->set('observability.alerts.pagerduty.integration_key', '');

        $outcome = app(PagerDutyObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::FAILED, $outcome);
    }

    public function test_send_returns_delivered_when_events_api_responds_ok(): void
    {
        Http::fake(['events.pagerduty.com/*' => Http::response(['status' => 'success'], 202)]);

        config()->set('observability.alerts.pagerduty.enabled', true);
        config()->set('observability.alerts.pagerduty.integration_key', 'key');

        $outcome = app(PagerDutyObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::DELIVERED, $outcome);
    }

    public function test_send_returns_failed_when_events_api_responds_non_2xx(): void
    {
        Http::fake(['events.pagerduty.com/*' => Http::response(['error' => 'bad key'], 400)]);

        config()->set('observability.alerts.pagerduty.enabled', true);
        config()->set('observability.alerts.pagerduty.integration_key', 'key');

        $outcome = app(PagerDutyObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::FAILED, $outcome);
    }

    public function test_send_clamps_unknown_severity_to_warning_when_delivered(): void
    {
        Http::fake(['events.pagerduty.com/*' => Http::response(['status' => 'success'], 202)]);

        config()->set('observability.alerts.pagerduty.enabled', true);
        config()->set('observability.alerts.pagerduty.integration_key', 'key');
        config()->set('observability.alerts.pagerduty.severity', 'bogus');

        $outcome = app(PagerDutyObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::DELIVERED, $outcome);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            /** @var array<string, mixed> $payload */
            $payload = $request->data();
            $innerPayload = is_array($payload['payload'] ?? null) ? $payload['payload'] : [];

            return ($innerPayload['severity'] ?? null) === 'warning';
        });
    }

    private function message(): ObservabilityAlertMessageDto
    {
        return new ObservabilityAlertMessageDto(
            subject: 'SLO failure',
            lines: ['window: 120 minutes', 'exit code: 1'],
        );
    }
}
