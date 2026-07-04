<?php

declare(strict_types=1);

namespace App\Support\Observability\Channels;

use App\Notifications\ObservabilitySloFailureNotification;
use App\Support\Data\TypedValue;
use App\Support\Observability\AlertDeliveryOutcome;
use App\Support\Observability\Contracts\ObservabilityAlertChannel;
use App\Support\Observability\Dto\ObservabilityAlertMessageDto;
use App\Support\Observability\ObservabilityAlertRoutingLogger;
use Illuminate\Support\Facades\Notification;

final readonly class EmailObservabilityAlertChannel implements ObservabilityAlertChannel
{
    public function __construct(private ObservabilityAlertRoutingLogger $routingLogger) {}

    public function channel(): string
    {
        return 'email';
    }

    public function send(ObservabilityAlertMessageDto $message): AlertDeliveryOutcome
    {
        if (! (bool) config('observability.alerts.email.enabled', false)) {
            return AlertDeliveryOutcome::DISABLED;
        }

        $configuredRecipients = config('observability.alerts.email.recipients', []);
        if (! is_array($configuredRecipients)) {
            $this->routingLogger->warning($this->channel(), 'Configured recipients value is not an array.');

            return AlertDeliveryOutcome::FAILED;
        }

        /** @var list<string> $recipients */
        $recipients = array_values(array_filter(array_map(
            static fn (mixed $value): string => TypedValue::trimmedString($value),
            $configuredRecipients,
        )));

        if ($recipients === []) {
            $this->routingLogger->warning($this->channel(), 'No recipients configured.');

            return AlertDeliveryOutcome::FAILED;
        }

        foreach ($recipients as $recipient) {
            Notification::route('mail', $recipient)
                ->notify(new ObservabilitySloFailureNotification($message->subject, $message->lines));
        }

        return AlertDeliveryOutcome::DELIVERED;
    }
}
