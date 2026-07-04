<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Scenarios;

use App\Domains\Catalog\Contracts\CatalogReadService;
use App\Support\Smoke\Performance\Contracts\PerformanceSmokeScenario;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeContextDto;

final class CatalogListColdPerformanceSmokeScenario implements PerformanceSmokeScenario
{
    public function __construct(
        private readonly CatalogReadService $catalogService,
    ) {}

    public function name(): string
    {
        return 'catalog_list_cold';
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
