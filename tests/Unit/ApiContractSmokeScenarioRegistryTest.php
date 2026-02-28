<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Smoke\ApiContract\ApiContractSmokeScenarioRegistry;
use App\Support\Smoke\ApiContract\Scenarios\AdminProductsApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\CartApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\CatalogApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\CheckoutApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\PaymentWebhookApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\ShippingWebhookApiContractScenario;
use App\Support\Smoke\SmokeScenarioSelector;
use InvalidArgumentException;
use Tests\TestCase;

class ApiContractSmokeScenarioRegistryTest extends TestCase
{
    public function test_scenarios_returns_selected_subset_in_declared_order(): void
    {
        $registry = new ApiContractSmokeScenarioRegistry(
            new CatalogApiContractScenario,
            new CartApiContractScenario,
            new CheckoutApiContractScenario,
            new AdminProductsApiContractScenario,
            new PaymentWebhookApiContractScenario,
            new ShippingWebhookApiContractScenario,
            new SmokeScenarioSelector,
        );

        $scenarios = $registry->scenarios(['shipping_webhook', 'catalog']);

        $this->assertCount(2, $scenarios);
        $this->assertSame(ShippingWebhookApiContractScenario::class, $scenarios[0]::class);
        $this->assertSame(CatalogApiContractScenario::class, $scenarios[1]::class);
    }

    public function test_scenarios_rejects_unknown_name(): void
    {
        $registry = new ApiContractSmokeScenarioRegistry(
            new CatalogApiContractScenario,
            new CartApiContractScenario,
            new CheckoutApiContractScenario,
            new AdminProductsApiContractScenario,
            new PaymentWebhookApiContractScenario,
            new ShippingWebhookApiContractScenario,
            new SmokeScenarioSelector,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option --only contains unknown api smoke scenario "unknown".');

        $registry->scenarios(['unknown']);
    }
}
