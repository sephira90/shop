<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Observability\Contracts\ObservabilityAlertChannel;
use App\Support\Observability\Dto\ObservabilityAlertMessageDto;
use App\Support\Observability\Dto\ObservabilityAlertPayloadDto;
use App\Support\Observability\ObservabilityAlertCooldownStore;
use App\Support\Observability\ObservabilityAlertMessageBuilder;
use App\Support\Observability\ObservabilityAlertRouter;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ObservabilityAlertRouterTest extends TestCase
{
    public function test_route_failure_alert_suppresses_delivery_when_cooldown_is_active(): void
    {
        config()->set('observability.alerts.cooldown_minutes', 60);
        Cache::flush();

        $cooldownStore = new ObservabilityAlertCooldownStore;
        $cooldownStore->remember();

        $emailChannel = $this->fakeChannel('email', true);
        $router = new ObservabilityAlertRouter(
            $cooldownStore,
            new ObservabilityAlertMessageBuilder,
            [$emailChannel],
        );

        $result = $router->routeFailureAlert($this->payload());

        $this->assertTrue($result->suppressed);
        $this->assertSame([], $result->sentChannels);
        $this->assertSame(0, $emailChannel->calls);
    }

    public function test_route_failure_alert_does_not_start_cooldown_when_all_channels_fail(): void
    {
        config()->set('observability.alerts.cooldown_minutes', 60);
        Cache::flush();

        $slackChannel = $this->fakeChannel('slack', false);
        $router = new ObservabilityAlertRouter(
            new ObservabilityAlertCooldownStore,
            new ObservabilityAlertMessageBuilder,
            [$slackChannel],
        );

        $firstAttempt = $router->routeFailureAlert($this->payload());
        $secondAttempt = $router->routeFailureAlert($this->payload());

        $this->assertFalse($firstAttempt->suppressed);
        $this->assertSame([], $firstAttempt->sentChannels);
        $this->assertFalse($secondAttempt->suppressed);
        $this->assertSame([], $secondAttempt->sentChannels);
        $this->assertSame(2, $slackChannel->calls);
    }

    public function test_route_failure_alert_returns_only_successful_channels_and_remembers_cooldown(): void
    {
        config()->set('observability.alerts.cooldown_minutes', 60);
        Cache::flush();

        $emailChannel = $this->fakeChannel('email', true);
        $slackChannel = $this->fakeChannel('slack', false);
        $pagerDutyChannel = $this->fakeChannel('pagerduty', true);

        $router = new ObservabilityAlertRouter(
            new ObservabilityAlertCooldownStore,
            new ObservabilityAlertMessageBuilder,
            [$emailChannel, $slackChannel, $pagerDutyChannel],
        );

        $firstAttempt = $router->routeFailureAlert($this->payload());
        $secondAttempt = $router->routeFailureAlert($this->payload());

        $this->assertFalse($firstAttempt->suppressed);
        $this->assertSame(['email', 'pagerduty'], $firstAttempt->sentChannels);
        $this->assertTrue($secondAttempt->suppressed);
        $this->assertSame(1, $emailChannel->calls);
        $this->assertSame(1, $slackChannel->calls);
        $this->assertSame(1, $pagerDutyChannel->calls);
    }

    private function payload(): ObservabilityAlertPayloadDto
    {
        return new ObservabilityAlertPayloadDto(
            command: 'app:observability-report',
            exitCode: 1,
            output: 'SLO failure output',
            parameters: ['--minutes' => '120'],
            happenedAt: '2026-02-28T14:30:00+00:00',
        );
    }

    private function fakeChannel(string $channel, bool $result): TestObservabilityAlertChannel
    {
        return new TestObservabilityAlertChannel($channel, $result);
    }
}

final class TestObservabilityAlertChannel implements ObservabilityAlertChannel
{
    public int $calls = 0;

    public function __construct(
        private readonly string $channelName,
        private readonly bool $result,
    ) {}

    public function channel(): string
    {
        return $this->channelName;
    }

    public function send(ObservabilityAlertMessageDto $message): bool
    {
        $this->calls++;

        return $this->result;
    }
}
