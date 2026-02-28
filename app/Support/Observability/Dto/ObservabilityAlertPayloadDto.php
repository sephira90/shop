<?php

declare(strict_types=1);

namespace App\Support\Observability\Dto;

final readonly class ObservabilityAlertPayloadDto
{
    /**
     * @param  array<string,string>  $parameters
     */
    public function __construct(
        public string $command,
        public int $exitCode,
        public string $output,
        public array $parameters,
        public string $happenedAt,
    ) {}
}
