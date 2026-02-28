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
            return new ObservabilityAlertRoutingResultDto([], true);
        }

        $message = $this->messageBuilder->buildFailureMessage($payload);
        $sent = [];

        foreach ($this->channels as $channel) {
            if ($channel->send($message)) {
                $sent[] = $channel->channel();
            }
        }

        if ($sent !== []) {
            $this->cooldownStore->remember();
        }

        return new ObservabilityAlertRoutingResultDto($sent, false);
    }
}
