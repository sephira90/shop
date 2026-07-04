<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * R3 guardrail: alert channel delivery outcomes are part of the architectural
 * contract and must not regress to the boolean-era shape.
 *
 * 1. The channel contract declares an explicit outcome return type and never
 *    a boolean.
 * 2. Every concrete channel implementation returns the outcome enum.
 * 3. The router resolves per-channel outcomes into the enriched DTO and emits
 *    the aggregate failure signal only when at least one channel was
 *    attempted and every attempt failed.
 * 4. The routing logger owns the aggregate failure event name.
 */
final class AlertDeliveryOutcomeContractGuardrailTest extends TestCase
{
    public function test_alert_channel_contract_requires_outcome_enum_return(): void
    {
        $source = File::get(app_path('Support/Observability/Contracts/ObservabilityAlertChannel.php'));

        $this->assertStringContainsString(
            'use App\Support\Observability\AlertDeliveryOutcome;',
            $source,
            'ObservabilityAlertChannel must import the AlertDeliveryOutcome enum.',
        );

        $this->assertStringContainsString(
            'public function send(ObservabilityAlertMessageDto $message): AlertDeliveryOutcome;',
            $source,
            'ObservabilityAlertChannel::send() must return AlertDeliveryOutcome, not bool.',
        );
    }

    public function test_no_concrete_channel_returns_bool(): void
    {
        $channels = File::allFiles(app_path('Support/Observability/Channels'));

        $this->assertNotEmpty($channels, 'Observability alert channels must exist.');

        foreach ($channels as $channelFile) {
            $source = $channelFile->getContents();

            $this->assertStringContainsString(
                'AlertDeliveryOutcome',
                $source,
                sprintf('%s must reference the AlertDeliveryOutcome enum.', $channelFile->getRelativePathname()),
            );

            $this->assertStringNotContainsString(
                '): bool',
                $source,
                sprintf('%s must not return bool; the channel contract requires an outcome enum.', $channelFile->getRelativePathname()),
            );
        }
    }

    public function test_router_resolves_outcomes_and_emits_aggregate_failure_signal(): void
    {
        $source = File::get(app_path('Support/Observability/ObservabilityAlertRouter.php'));

        $this->assertStringContainsString(
            'isDelivered()',
            $source,
            'Router must classify delivered channels by outcome predicate.',
        );

        $this->assertStringContainsString(
            'isFailed()',
            $source,
            'Router must classify failed channels by outcome predicate.',
        );

        $this->assertStringContainsString(
            'aggregateFailure(',
            $source,
            'Router must emit the aggregate failure signal when every attempted delivery failed.',
        );

        $this->assertStringContainsString(
            '$delivered !== []',
            $source,
            'Router must remember cooldown only after at least one successful delivery.',
        );
    }

    public function test_routing_result_dto_exposes_three_outcome_buckets(): void
    {
        $source = File::get(app_path('Support/Observability/Dto/ObservabilityAlertRoutingResultDto.php'));

        $this->assertStringContainsString('public array $deliveredChannels', $source);
        $this->assertStringContainsString('public array $disabledChannels', $source);
        $this->assertStringContainsString('public array $failedChannels', $source);
        $this->assertStringContainsString('public function hasAttemptedDeliveries()', $source);
        $this->assertStringContainsString('public function everyAttemptedDeliveryFailed()', $source);
    }

    public function test_routing_logger_owns_aggregate_failure_event_name(): void
    {
        $source = File::get(app_path('Support/Observability/ObservabilityAlertRoutingLogger.php'));

        $this->assertStringContainsString(
            'observability.alert_routing_aggregate_failure',
            $source,
            'ObservabilityAlertRoutingLogger must own the aggregate failure event name.',
        );

        $this->assertStringContainsString(
            'public function aggregateFailure(',
            $source,
            'ObservabilityAlertRoutingLogger must expose aggregateFailure() method.',
        );
    }
}
