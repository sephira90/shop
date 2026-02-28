<?php

declare(strict_types=1);

namespace App\Support\Observability\Dto;

use App\Support\Operations\Dto\ConsoleCommandResultDto;

final readonly class ObservabilityAlertCheckRunResultDto
{
    public function __construct(
        public ConsoleCommandResultDto $reportResult,
        public ?ObservabilityAlertRoutingResultDto $routingResult,
    ) {}

    public function passed(): bool
    {
        return $this->reportResult->succeeded();
    }
}
