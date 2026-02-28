<?php

declare(strict_types=1);

namespace App\Support\Observability\Dto;

final readonly class ObservabilityAlertMessageDto
{
    /**
     * @param  list<string>  $lines
     */
    public function __construct(
        public string $subject,
        public array $lines,
    ) {}
}
