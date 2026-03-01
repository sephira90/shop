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
        public int $totalMatched,
        public int $totalAffected,
        public int $totalBatches,
    ) {}

    /**
     * @param  list<MaintenanceCleanupResourceResultDto>  $resources
     */
    public static function fromResources(bool $dryRun, array $resources): self
    {
        return new self(
            dryRun: $dryRun,
            resources: $resources,
            totalMatched: array_sum(array_map(
                static fn (MaintenanceCleanupResourceResultDto $resource): int => $resource->matched,
                $resources,
            )),
            totalAffected: array_sum(array_map(
                static fn (MaintenanceCleanupResourceResultDto $resource): int => $resource->affected,
                $resources,
            )),
            totalBatches: array_sum(array_map(
                static fn (MaintenanceCleanupResourceResultDto $resource): int => $resource->batches,
                $resources,
            )),
        );
    }
}
