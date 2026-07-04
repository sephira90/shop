<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Observability\Channels;

use App\Support\Observability\AlertDeliveryOutcome;
use App\Support\Observability\Channels\SlackObservabilityAlertChannel;
use App\Support\Observability\Dto\ObservabilityAlertMessageDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifies the Slack channel surfaces an explicit outcome: disabled when
 * the flag is off, failed on missing webhook URL or non-2xx response, and
 * delivered on a successful webhook post.
 */
class SlackObservabilityAlertChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_returns_disabled_when_channel_flag_is_off(): void
    {
        config()->set('observability.alerts.slack.enabled', false);
        config()->set('observability.alerts.slack.webhook_url', 'https://hooks.slack.com/services/abc');

        $outcome = app(SlackObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::DISABLED, $outcome);
    }

    public function test_send_returns_failed_when_webhook_url_is_empty(): void
    {
        config()->set('observability.alerts.slack.enabled', true);
        config()->set('observability.alerts.slack.webhook_url', '');

        $outcome = app(SlackObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::FAILED, $outcome);
    }

    public function test_send_returns_delivered_when_webhook_responds_ok(): void
    {
        Http::fake(['hooks.slack.com/*' => Http::response('', 200)]);

        config()->set('observability.alerts.slack.enabled', true);
        config()->set('observability.alerts.slack.webhook_url', 'https://hooks.slack.com/services/abc');

        $outcome = app(SlackObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::DELIVERED, $outcome);
    }

    public function test_send_returns_failed_when_webhook_responds_non_2xx(): void
    {
        Http::fake(['hooks.slack.com/*' => Http::response('bad payload', 400)]);

        config()->set('observability.alerts.slack.enabled', true);
        config()->set('observability.alerts.slack.webhook_url', 'https://hooks.slack.com/services/abc');

        $outcome = app(SlackObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::FAILED, $outcome);
    }

    private function message(): ObservabilityAlertMessageDto
    {
        return new ObservabilityAlertMessageDto(
            subject: 'SLO failure',
            lines: ['window: 120 minutes', 'exit code: 1'],
        );
    }
}
