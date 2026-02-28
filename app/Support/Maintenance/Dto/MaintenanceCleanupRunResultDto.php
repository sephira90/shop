<?php

declare(strict_types=1);

namespace App\Support\Maintenance\Dto;

final readonly class MaintenanceCleanupRunResultDto
{
    /**
     * @param  list<MaintenanceCleanupResourceResultDto>  $resources
     */
    public function __construct(
        public bool $dryRun,
        public array $resources,
    ) {}
}
