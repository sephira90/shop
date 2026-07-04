<?php

declare(strict_types=1);

namespace App\Support\Observability\Channels;

use App\Support\Data\TypedValue;
use App\Support\Observability\AlertDeliveryOutcome;
use App\Support\Observability\Contracts\ObservabilityAlertChannel;
use App\Support\Observability\Dto\ObservabilityAlertMessageDto;
use App\Support\Observability\ObservabilityAlertRoutingLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final readonly class PagerDutyObservabilityAlertChannel implements ObservabilityAlertChannel
{
    public function __construct(private ObservabilityAlertRoutingLogger $routingLogger) {}

    public function channel(): string
    {
        return 'pagerduty';
    }

    public function send(ObservabilityAlertMessageDto $message): AlertDeliveryOutcome
    {
        if (! (bool) config('observability.alerts.pagerduty.enabled', false)) {
            return AlertDeliveryOutcome::DISABLED;
        }

        $integrationKey = TypedValue::trimmedString(config('observability.alerts.pagerduty.integration_key', ''));
        if ($integrationKey === '') {
            $this->routingLogger->warning($this->channel(), 'PagerDuty integration key is empty.');

            return AlertDeliveryOutcome::FAILED;
        }

        $severity = strtolower(TypedValue::trimmedString(config('observability.alerts.pagerduty.severity', 'warning')));
        if (! in_array($severity, ['critical', 'error', 'warning', 'info'], true)) {
            $severity = 'warning';
        }

        try {
            $response = Http::timeout(10)->post('https://events.pagerduty.com/v2/enqueue', [
                'routing_key' => $integrationKey,
                'event_action' => 'trigger',
                'payload' => [
                    'summary' => $message->subject,
                    'source' => TypedValue::string(config('app.url', 'unknown')),
                    'severity' => $severity,
                    'custom_details' => [
                        'message' => implode("\n", $message->lines),
                    ],
                ],
            ]);
        } catch (Throwable $exception) {
            $this->routingLogger->warning($this->channel(), 'PagerDuty request threw an exception.', [
                'exception' => $exception->getMessage(),
            ]);

            return AlertDeliveryOutcome::FAILED;
        }

        if (! $response->successful()) {
            $this->routingLogger->warning($this->channel(), 'PagerDuty request failed.', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return AlertDeliveryOutcome::FAILED;
        }

        return AlertDeliveryOutcome::DELIVERED;
    }
}
