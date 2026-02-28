<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Support\Smoke\ApiContract\ApiContractSmokeScenarioRegistry;
use App\Support\Smoke\Performance\PerformanceSmokeScenarioRegistry;
use Tests\TestCase;

class SmokeScenarioRegistryContractTest extends TestCase
{
    public function test_api_contract_scenario_names_are_stable_and_unique(): void
    {
        $names = app(ApiContractSmokeScenarioRegistry::class)->names();

        $this->assertSame(
            ['catalog', 'cart', 'checkout', 'admin_products', 'payment_webhook', 'shipping_webhook'],
            $names,
        );
        $this->assertCount(count(array_unique($names)), $names);
    }

    public function test_performance_scenario_names_are_stable_and_unique(): void
    {
        $names = app(PerformanceSmokeScenarioRegistry::class)->names();

        $this->assertSame(
            [
                'catalog_list_cold',
                'catalog_list_warm',
                'cart_show',
                'checkout_place_order',
                'admin_orders_summary',
                'admin_products_list',
            ],
            $names,
        );
        $this->assertCount(count(array_unique($names)), $names);
    }
}
