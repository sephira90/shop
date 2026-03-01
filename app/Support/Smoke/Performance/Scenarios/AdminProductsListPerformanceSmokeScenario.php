<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Scenarios;

use App\Repositories\AdminProductReadRepository;
use App\Support\Smoke\Performance\Contracts\PerformanceSmokeScenario;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeContextDto;

final class AdminProductsListPerformanceSmokeScenario implements PerformanceSmokeScenario
{
    public function __construct(
        private readonly AdminProductReadRepository $adminProductReadRepository,
    ) {}

    public function name(): string
    {
        return 'admin_products_list';
    }

    public function usesRollback(): bool
    {
        return false;
    }

    public function run(PerformanceSmokeContextDto $context): void
    {
        $this->adminProductReadRepository->paginateForAdmin($context->productFilter);
    }
}
