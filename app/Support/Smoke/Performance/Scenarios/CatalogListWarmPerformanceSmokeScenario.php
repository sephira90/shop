<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Scenarios;

use App\Services\Catalog\CatalogService;
use App\Support\Smoke\Performance\Contracts\PerformanceSmokeScenario;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeContextDto;

final class CatalogListWarmPerformanceSmokeScenario implements PerformanceSmokeScenario
{
    public function __construct(
        private readonly CatalogService $catalogService,
    ) {}

    public function name(): string
    {
        return 'catalog_list_warm';
    }

    public function usesRollback(): bool
    {
        return false;
    }

    public function run(PerformanceSmokeContextDto $context): void
    {
        $this->catalogService->list($context->catalogFilter, 12);
    }
}
