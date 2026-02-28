<?php

declare(strict_types=1);

namespace App\Support\Observability\Dto;

final readonly class ObservabilityReportOutputDto
{
    /**
     * @param  list<string>  $summaryHeaders
     * @param  list<list<string>>  $summaryRows
     * @param  list<string>  $catalogHeaders
     * @param  list<list<string>>  $catalogRows
     * @param  list<string>  $webhookHeaders
     * @param  list<list<string>>  $webhookRows
     */
    public function __construct(
        public ?string $jsonOutput,
        public array $summaryHeaders,
        public array $summaryRows,
        public array $catalogHeaders,
        public array $catalogRows,
        public ?string $catalogEmptyMessage,
        public array $webhookHeaders,
        public array $webhookRows,
        public ?string $webhookEmptyMessage,
    ) {}
}
