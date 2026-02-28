<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Contracts;

use App\Support\Smoke\Performance\Dto\PerformanceSmokeContextDto;

interface PerformanceSmokeScenario
{
    public function name(): string;

    public function usesRollback(): bool;

    public function run(PerformanceSmokeContextDto $context): void;
}
