<?php

declare(strict_types=1);

namespace App\Support\Maintenance;

use App\Support\Maintenance\Dto\MaintenanceCleanupResourceResultDto;
use App\Support\Maintenance\Dto\MaintenanceCleanupRetentionDto;
use App\Support\Maintenance\Dto\MaintenanceCleanupRunResultDto;
use Carbon\CarbonImmutable;

final class MaintenanceCleanupExecutor
{
    public function __construct(private readonly MaintenanceCleanupPlanFactory $planFactory) {}

    public function run(
        MaintenanceCleanupRetentionDto $retention,
        bool $dryRun,
        ?CarbonImmutable $now = null,
    ): MaintenanceCleanupRunResultDto {
        $now ??= CarbonImmutable::now();
        $batchSize = $this->resolveBatchSize();
        /** @var list<MaintenanceCleanupResourceResultDto> $resources */
        $resources = [];

        foreach ($this->planFactory->build($retention, $now) as $step) {
            $resources[] = $step->resource->cleanup($step->cutoff, $dryRun, $batchSize);
        }

        return MaintenanceCleanupRunResultDto::fromResources($dryRun, $resources);
    }

    private function resolveBatchSize(): int
    {
        $configured = filter_var(config('cleanup.batch_size', 500), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $configured === false ? 500 : (int) $configured;
    }
}
