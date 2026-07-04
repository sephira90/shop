<?php

declare(strict_types=1);

namespace App\Support\Orders\Dto;

final readonly class OrdersReconcileOutputDto
{
    /**
     * @param  list<string>  $findingsHeaders
     * @param  list<list<string|null>>  $findingsRows
     */
    public function __construct(
        public ?string $jsonOutput,
        public array $findingsHeaders,
        public array $findingsRows,
        public ?string $cleanMessage,
    ) {}
}
