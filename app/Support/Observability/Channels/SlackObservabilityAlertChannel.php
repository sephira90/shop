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

final readonly class SlackObservabilityAlertChannel implements ObservabilityAlertChannel
{
    public function __construct(private ObservabilityAlertRoutingLogger $routingLogger) {}

    public function channel(): string
    {
        return 'slack';
    }

    public function send(ObservabilityAlertMessageDto $message): AlertDeliveryOutcome
    {
        if (! (bool) config('observability.alerts.slack.enabled', false)) {
            return AlertDeliveryOutcome::DISABLED;
        }

        $webhookUrl = TypedValue::trimmedString(config('observability.alerts.slack.webhook_url', ''));
        if ($webhookUrl === '') {
            $this->routingLogger->warning($this->channel(), 'Slack webhook URL is empty.');

            return AlertDeliveryOutcome::FAILED;
        }

        try {
            $response = Http::timeout(10)->post($webhookUrl, [
                'text' => "*{$message->subject}*\n".implode("\n", $message->lines),
            ]);
        } catch (Throwable $exception) {
            $this->routingLogger->warning($this->channel(), 'Slack request threw an exception.', [
                'exception' => $exception->getMessage(),
            ]);

            return AlertDeliveryOutcome::FAILED;
        }

        if (! $response->successful()) {
            $this->routingLogger->warning($this->channel(), 'Slack webhook request failed.', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return AlertDeliveryOutcome::FAILED;
        }

        return AlertDeliveryOutcome::DELIVERED;
    }
}
