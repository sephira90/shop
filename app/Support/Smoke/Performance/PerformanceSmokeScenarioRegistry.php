<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance;

use App\Support\Smoke\Performance\Contracts\PerformanceSmokeScenario;
use App\Support\Smoke\Performance\Scenarios\AdminOrdersSummaryPerformanceSmokeScenario;
use App\Support\Smoke\Performance\Scenarios\AdminProductsListPerformanceSmokeScenario;
use App\Support\Smoke\Performance\Scenarios\CartShowPerformanceSmokeScenario;
use App\Support\Smoke\Performance\Scenarios\CatalogListColdPerformanceSmokeScenario;
use App\Support\Smoke\Performance\Scenarios\CatalogListWarmPerformanceSmokeScenario;
use App\Support\Smoke\Performance\Scenarios\CheckoutPlaceOrderPerformanceSmokeScenario;
use App\Support\Smoke\SmokeScenarioSelector;

final class PerformanceSmokeScenarioRegistry
{
    public function __construct(
        private readonly CatalogListColdPerformanceSmokeScenario $catalogListColdScenario,
        private readonly CatalogListWarmPerformanceSmokeScenario $catalogListWarmScenario,
        private readonly CartShowPerformanceSmokeScenario $cartShowScenario,
        private readonly CheckoutPlaceOrderPerformanceSmokeScenario $checkoutPlaceOrderScenario,
        private readonly AdminOrdersSummaryPerformanceSmokeScenario $adminOrdersSummaryScenario,
        private readonly AdminProductsListPerformanceSmokeScenario $adminProductsListScenario,
        private readonly SmokeScenarioSelector $scenarioSelector,
    ) {}

    /**
     * @param  list<string>  $only
     * @return list<PerformanceSmokeScenario>
     */
    public function scenarios(array $only): array
    {
        return $this->scenarioSelector->select($this->orderedScenarios(), $only, 'performance smoke');
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->scenarioSelector->names($this->orderedScenarios());
    }

    /**
     * @return array<string,PerformanceSmokeScenario>
     */
    private function orderedScenarios(): array
    {
        return [
            'catalog_list_cold' => $this->catalogListColdScenario,
            'catalog_list_warm' => $this->catalogListWarmScenario,
            'cart_show' => $this->cartShowScenario,
            'checkout_place_order' => $this->checkoutPlaceOrderScenario,
            'admin_orders_summary' => $this->adminOrdersSummaryScenario,
            'admin_products_list' => $this->adminProductsListScenario,
        ];
    }
}
