<?php

declare(strict_types=1);

namespace App\Support\Maintenance\Dto;

final readonly class MaintenanceCleanupResourceResultDto
{
    public function __construct(
        public string $resource,
        public string $cutoffUtc,
        public int $matched,
        public int $affected,
    ) {}
}
