<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Observability;

use App\Support\Observability\AlertDeliveryOutcome;
use App\Support\Observability\Contracts\ObservabilityAlertChannel;
use App\Support\Observability\Dto\ObservabilityAlertMessageDto;
use App\Support\Observability\Dto\ObservabilityAlertPayloadDto;
use App\Support\Observability\ObservabilityAlertCooldownStore;
use App\Support\Observability\ObservabilityAlertMessageBuilder;
use App\Support\Observability\ObservabilityAlertRouter;
use App\Support\Observability\ObservabilityAlertRoutingLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Verifies the alert router resolves explicit per-channel outcomes into the
 * R3 contract: cooldown stays disabled-only behavior, the aggregate failure
 * signal fires only when at least one channel was attempted and every
 * attempt failed, and disabled channels never trigger aggregate warnings.
 */
class ObservabilityAlertRouterTest extends TestCase
{
    public function test_route_failure_alert_suppresses_delivery_when_cooldown_is_active(): void
    {
        config()->set('observability.alerts.cooldown_minutes', 60);
        Cache::flush();

        $cooldownStore = new ObservabilityAlertCooldownStore;
        $cooldownStore->remember();

        $emailChannel = $this->stubChannel('email', AlertDeliveryOutcome::DELIVERED);
        $router = $this->makeRouter($cooldownStore, [$emailChannel]);

        $result = $router->routeFailureAlert($this->payload());

        $this->assertTrue($result->suppressed);
        $this->assertSame([], $result->deliveredChannels);
        $this->assertSame(0, $emailChannel->calls);
    }

    public function test_route_failure_alert_classifies_all_disabled_channels_without_aggregate_warning(): void
    {
        Log::shouldReceive('warning')
            ->never()
            ->with('observability.alert_routing_aggregate_failure', \Mockery::any());

        $emailChannel = $this->stubChannel('email', AlertDeliveryOutcome::DISABLED);
        $slackChannel = $this->stubChannel('slack', AlertDeliveryOutcome::DISABLED);

        $router = $this->makeRouter(new ObservabilityAlertCooldownStore, [$emailChannel, $slackChannel]);

        $result = $router->routeFailureAlert($this->payload());

        $this->assertFalse($result->suppressed);
        $this->assertSame([], $result->deliveredChannels);
        $this->assertSame(['email', 'slack'], $result->disabledChannels);
        $this->assertSame([], $result->failedChannels);
        $this->assertFalse($result->hasAttemptedDeliveries());
        $this->assertFalse($result->everyAttemptedDeliveryFailed());
    }

    public function test_route_failure_alert_emits_aggregate_warning_when_every_attempted_channel_fails(): void
    {
        $warningFired = false;
        Log::spy();
        Log::shouldReceive('warning')
            ->with('observability.alert_routing_aggregate_failure', \Mockery::on(function (array $context) use (&$warningFired): bool {
                $warningFired = true;

                return $context['channels'] === ['slack', 'pagerduty']
                    && array_key_exists('correlation_id', $context) === false;
            }));

        $slackChannel = $this->stubChannel('slack', AlertDeliveryOutcome::FAILED);
        $pagerDutyChannel = $this->stubChannel('pagerduty', AlertDeliveryOutcome::FAILED);

        $router = $this->makeRouter(new ObservabilityAlertCooldownStore, [$slackChannel, $pagerDutyChannel]);

        $result = $router->routeFailureAlert($this->payload());

        $this->assertTrue($warningFired);
        $this->assertFalse($result->suppressed);
        $this->assertSame([], $result->deliveredChannels);
        $this->assertSame([], $result->disabledChannels);
        $this->assertSame(['slack', 'pagerduty'], $result->failedChannels);
        $this->assertTrue($result->everyAttemptedDeliveryFailed());
    }

    public function test_route_failure_alert_does_not_emit_aggregate_warning_on_partial_success(): void
    {
        Log::shouldReceive('warning')
            ->never()
            ->with('observability.alert_routing_aggregate_failure', \Mockery::any());

        $emailChannel = $this->stubChannel('email', AlertDeliveryOutcome::DELIVERED);
        $slackChannel = $this->stubChannel('slack', AlertDeliveryOutcome::FAILED);
        $pagerDutyChannel = $this->stubChannel('pagerduty', AlertDeliveryOutcome::DISABLED);

        $router = $this->makeRouter(new ObservabilityAlertCooldownStore, [$emailChannel, $slackChannel, $pagerDutyChannel]);

        $result = $router->routeFailureAlert($this->payload());

        $this->assertFalse($result->suppressed);
        $this->assertSame(['email'], $result->deliveredChannels);
        $this->assertSame(['pagerduty'], $result->disabledChannels);
        $this->assertSame(['slack'], $result->failedChannels);
        $this->assertTrue($result->hasAttemptedDeliveries());
        $this->assertFalse($result->everyAttemptedDeliveryFailed());
    }

    public function test_route_failure_alert_returns_only_delivered_channels_and_remembers_cooldown(): void
    {
        config()->set('observability.alerts.cooldown_minutes', 60);
        Cache::flush();

        $emailChannel = $this->stubChannel('email', AlertDeliveryOutcome::DELIVERED);
        $slackChannel = $this->stubChannel('slack', AlertDeliveryOutcome::FAILED);
        $pagerDutyChannel = $this->stubChannel('pagerduty', AlertDeliveryOutcome::DELIVERED);

        $router = $this->makeRouter(new ObservabilityAlertCooldownStore, [$emailChannel, $slackChannel, $pagerDutyChannel]);

        $firstAttempt = $router->routeFailureAlert($this->payload());
        $secondAttempt = $router->routeFailureAlert($this->payload());

        $this->assertFalse($firstAttempt->suppressed);
        $this->assertSame(['email', 'pagerduty'], $firstAttempt->deliveredChannels);
        $this->assertTrue($secondAttempt->suppressed);
        $this->assertSame(1, $emailChannel->calls);
        $this->assertSame(1, $slackChannel->calls);
        $this->assertSame(1, $pagerDutyChannel->calls);
    }

    public function test_route_failure_alert_does_not_start_cooldown_when_no_delivery_succeeds(): void
    {
        config()->set('observability.alerts.cooldown_minutes', 60);
        Cache::flush();

        Log::shouldReceive('warning')->with('observability.alert_routing_aggregate_failure', \Mockery::any());

        $slackChannel = $this->stubChannel('slack', AlertDeliveryOutcome::FAILED);
        $router = $this->makeRouter(new ObservabilityAlertCooldownStore, [$slackChannel]);

        $firstAttempt = $router->routeFailureAlert($this->payload());
        $secondAttempt = $router->routeFailureAlert($this->payload());

        $this->assertFalse($firstAttempt->suppressed);
        $this->assertSame([], $firstAttempt->deliveredChannels);
        $this->assertFalse($secondAttempt->suppressed);
        $this->assertSame([], $secondAttempt->deliveredChannels);
        $this->assertSame(2, $slackChannel->calls);
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

    /**
     * @param  list<ObservabilityAlertChannel>  $channels
     */
    private function makeRouter(ObservabilityAlertCooldownStore $cooldownStore, array $channels): ObservabilityAlertRouter
    {
        return new ObservabilityAlertRouter(
            $cooldownStore,
            new ObservabilityAlertMessageBuilder,
            app(ObservabilityAlertRoutingLogger::class),
            $channels,
        );
    }

    private function stubChannel(string $channel, AlertDeliveryOutcome $outcome): TestObservabilityAlertChannel
    {
        return new TestObservabilityAlertChannel($channel, $outcome);
    }
}

final class TestObservabilityAlertChannel implements ObservabilityAlertChannel
{
    public int $calls = 0;

    public function __construct(
        private readonly string $channelName,
        private readonly AlertDeliveryOutcome $outcome,
    ) {}

    public function channel(): string
    {
        return $this->channelName;
    }

    public function send(ObservabilityAlertMessageDto $message): AlertDeliveryOutcome
    {
        $this->calls++;

        return $this->outcome;
    }
}
