<?php

declare(strict_types=1);

namespace App\Support\Maintenance\Contracts;

use App\Support\Maintenance\Dto\MaintenanceCleanupResourceResultDto;
use App\Support\Maintenance\Dto\MaintenanceCleanupRetentionDto;
use Carbon\CarbonImmutable;

interface MaintenanceCleanupResource
{
    /**
     * Return stable resource identifier for reporting.
     */
    public function resource(): string;

    /**
     * Resolve cutoff for this resource from retention policy and reference time.
     */
    public function cutoff(CarbonImmutable $now, MaintenanceCleanupRetentionDto $retention): CarbonImmutable;

    /**
     * Execute cleanup for this resource with deterministic batch sizing.
     */
    public function cleanup(
        CarbonImmutable $cutoff,
        bool $dryRun,
        int $batchSize,
    ): MaintenanceCleanupResourceResultDto;
}
