<?php

declare(strict_types=1);

namespace App\Support\Maintenance;

use App\Support\Maintenance\Contracts\MaintenanceCleanupResource;
use App\Support\Maintenance\Dto\MaintenanceCleanupPlanItemDto;
use App\Support\Maintenance\Dto\MaintenanceCleanupRetentionDto;
use Carbon\CarbonImmutable;

final class MaintenanceCleanupPlanFactory
{
    /**
     * @var list<MaintenanceCleanupResource>
     */
    private array $resources;

    /**
     * @param  iterable<MaintenanceCleanupResource>  $resources
     */
    public function __construct(iterable $resources)
    {
        $this->resources = is_array($resources)
            ? array_values($resources)
            : iterator_to_array($resources, false);
    }

    /**
     * @return list<MaintenanceCleanupPlanItemDto>
     */
    public function build(
        MaintenanceCleanupRetentionDto $retention,
        CarbonImmutable $now,
    ): array {
        $plan = [];

        foreach ($this->resources as $resource) {
            $plan[] = new MaintenanceCleanupPlanItemDto(
                resource: $resource,
                cutoff: $resource->cutoff($now, $retention),
            );
        }

        return $plan;
    }
}
