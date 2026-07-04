<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Observability\Channels;

use App\Notifications\ObservabilitySloFailureNotification;
use App\Support\Observability\AlertDeliveryOutcome;
use App\Support\Observability\Channels\EmailObservabilityAlertChannel;
use App\Support\Observability\Dto\ObservabilityAlertMessageDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Verifies the email channel surfaces an explicit outcome instead of a
 * boolean: disabled when the channel flag is off, delivered when at least
 * one recipient receives the notification, and failed when configuration
 * rejects the delivery (no/invalid recipients). Per-channel warnings stay
 * attached to configuration failures only.
 */
class EmailObservabilityAlertChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_returns_disabled_when_channel_flag_is_off(): void
    {
        config()->set('observability.alerts.email.enabled', false);
        config()->set('observability.alerts.email.recipients', ['oncall@example.com']);

        $outcome = app(EmailObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::DISABLED, $outcome);
    }

    public function test_send_returns_delivered_when_notification_reaches_recipients(): void
    {
        Notification::fake();
        config()->set('observability.alerts.email.enabled', true);
        config()->set('observability.alerts.email.recipients', ['oncall@example.com', 'backup@example.com']);

        $outcome = app(EmailObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::DELIVERED, $outcome);

        Notification::assertSentTimes(ObservabilitySloFailureNotification::class, 2);
    }

    public function test_send_returns_failed_when_no_recipients_configured(): void
    {
        config()->set('observability.alerts.email.enabled', true);
        config()->set('observability.alerts.email.recipients', []);

        $outcome = app(EmailObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::FAILED, $outcome);
    }

    public function test_send_returns_failed_when_recipients_value_is_not_an_array(): void
    {
        config()->set('observability.alerts.email.enabled', true);
        config()->set('observability.alerts.email.recipients', 'oncall@example.com');

        $outcome = app(EmailObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::FAILED, $outcome);
    }

    public function test_send_filters_blank_recipients_and_delivers_when_at_least_one_remains(): void
    {
        Notification::fake();
        config()->set('observability.alerts.email.enabled', true);
        config()->set('observability.alerts.email.recipients', ['', '  ', 'oncall@example.com']);

        $outcome = app(EmailObservabilityAlertChannel::class)->send($this->message());

        $this->assertSame(AlertDeliveryOutcome::DELIVERED, $outcome);

        Notification::assertSentTimes(ObservabilitySloFailureNotification::class, 1);
    }

    private function message(): ObservabilityAlertMessageDto
    {
        return new ObservabilityAlertMessageDto(
            subject: 'SLO failure',
            lines: ['window: 120 minutes', 'exit code: 1'],
        );
    }
}
