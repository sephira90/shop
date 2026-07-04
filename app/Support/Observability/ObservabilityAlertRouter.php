<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Observability\Contracts\ObservabilityAlertChannel;
use App\Support\Observability\Dto\ObservabilityAlertPayloadDto;
use App\Support\Observability\Dto\ObservabilityAlertRoutingResultDto;

final class ObservabilityAlertRouter
{
    /**
     * @var list<ObservabilityAlertChannel>
     */
    private array $channels;

    /**
     * @param  iterable<ObservabilityAlertChannel>  $channels
     */
    public function __construct(
        private readonly ObservabilityAlertCooldownStore $cooldownStore,
        private readonly ObservabilityAlertMessageBuilder $messageBuilder,
        private readonly ObservabilityAlertRoutingLogger $routingLogger,
        iterable $channels,
    ) {
        $this->channels = is_array($channels) ? array_values($channels) : iterator_to_array($channels, false);
    }

    /**
     * Route failed observability check alert to configured channels.
     */
    public function routeFailureAlert(ObservabilityAlertPayloadDto $payload): ObservabilityAlertRoutingResultDto
    {
        if ($this->cooldownStore->isSuppressed()) {
            return new ObservabilityAlertRoutingResultDto(
                deliveredChannels: [],
                disabledChannels: [],
                failedChannels: [],
                suppressed: true,
            );
        }

        $message = $this->messageBuilder->buildFailureMessage($payload);
        $delivered = [];
        $disabled = [];
        $failed = [];

        foreach ($this->channels as $channel) {
            $outcome = $channel->send($message);

            if ($outcome->isDelivered()) {
                $delivered[] = $channel->channel();
            } elseif ($outcome->isFailed()) {
                $failed[] = $channel->channel();
            } else {
                $disabled[] = $channel->channel();
            }
        }

        if ($delivered !== []) {
            $this->cooldownStore->remember();
        } elseif ($failed !== []) {
            // At least one enabled channel attempted delivery and every attempt
            // failed: emit the aggregate operational signal so the failure does
            // not stay hidden behind per-channel warnings alone.
            $this->routingLogger->aggregateFailure($failed);
        }

        return new ObservabilityAlertRoutingResultDto(
            deliveredChannels: $delivered,
            disabledChannels: $disabled,
            failedChannels: $failed,
            suppressed: false,
        );
    }
}
