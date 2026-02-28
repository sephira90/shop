<?php

declare(strict_types=1);

namespace App\Support\Maintenance\Dto;

final readonly class MaintenanceCleanupRetentionDto
{
    public function __construct(
        public int $idempotencyHours,
        public int $webhookHours,
        public int $activeCartHours,
        public int $inactiveCartHours,
    ) {}
}
