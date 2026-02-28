<?php

declare(strict_types=1);

namespace App\Support\Observability\Dto;

final readonly class ObservabilityAlertRoutingResultDto
{
    /**
     * @param  list<string>  $sentChannels
     */
    public function __construct(
        public array $sentChannels,
        public bool $suppressed,
    ) {}

    public function hasSentChannels(): bool
    {
        return $this->sentChannels !== [];
    }
}
