<?php

declare(strict_types=1);

namespace App\Support\Observability\Dto;

final readonly class ObservabilityReportOptionsDto
{
    public function __construct(
        public int $minutes,
        public string $source,
        public ?float $maxApiSlowRate,
        public ?float $maxWebhookLagWarnRate,
        public bool $requireApiSamples,
        public bool $requireWebhookSamples,
        public bool $json,
    ) {}
}
