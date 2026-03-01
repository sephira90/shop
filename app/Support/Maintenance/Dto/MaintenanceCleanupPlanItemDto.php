<?php

declare(strict_types=1);

namespace App\Support\Maintenance\Dto;

use App\Support\Maintenance\Contracts\MaintenanceCleanupResource;
use Carbon\CarbonImmutable;

final readonly class MaintenanceCleanupPlanItemDto
{
    public function __construct(
        public MaintenanceCleanupResource $resource,
        public CarbonImmutable $cutoff,
    ) {}
}
