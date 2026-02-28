<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Scenarios;

use App\Repositories\OrderRepository;
use App\Support\Smoke\Performance\Contracts\PerformanceSmokeScenario;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeContextDto;

final class AdminOrdersSummaryPerformanceSmokeScenario implements PerformanceSmokeScenario
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {}

    public function name(): string
    {
        return 'admin_orders_summary';
    }

    public function usesRollback(): bool
    {
        return false;
    }

    public function run(PerformanceSmokeContextDto $context): void
    {
        $this->orderRepository->paginateSummaryForAdmin($context->orderFilter);
    }
}
