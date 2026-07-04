<?php

declare(strict_types=1);

namespace App\Support\Observability\Dto;

final readonly class ObservabilityAlertRoutingResultDto
{
    /**
     * @param  list<string>  $deliveredChannels  Channels that accepted and delivered the alert.
     * @param  list<string>  $disabledChannels  Channels disabled by configuration; no delivery attempted.
     * @param  list<string>  $failedChannels  Channels that attempted delivery and failed.
     */
    public function __construct(
        public array $deliveredChannels,
        public array $disabledChannels,
        public array $failedChannels,
        public bool $suppressed,
    ) {}

    public function hasSentChannels(): bool
    {
        return $this->deliveredChannels !== [];
    }

    /**
     * Whether at least one enabled channel attempted delivery. Disabled
     * channels never count as an attempt; the aggregate all-failed signal
     * applies only when this returns true and every attempt failed.
     */
    public function hasAttemptedDeliveries(): bool
    {
        return $this->deliveredChannels !== [] || $this->failedChannels !== [];
    }

    public function everyAttemptedDeliveryFailed(): bool
    {
        return $this->deliveredChannels === [] && $this->failedChannels !== [];
    }
}
