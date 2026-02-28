<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract;

use App\Support\Smoke\ApiContract\Scenarios\AdminProductsApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\CartApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\CatalogApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\CheckoutApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\PaymentWebhookApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\ShippingWebhookApiContractScenario;
use App\Support\Smoke\SmokeScenarioSelector;

final class ApiContractSmokeScenarioRegistry
{
    public function __construct(
        private readonly CatalogApiContractScenario $catalogScenario,
        private readonly CartApiContractScenario $cartScenario,
        private readonly CheckoutApiContractScenario $checkoutScenario,
        private readonly AdminProductsApiContractScenario $adminProductsScenario,
        private readonly PaymentWebhookApiContractScenario $paymentWebhookScenario,
        private readonly ShippingWebhookApiContractScenario $shippingWebhookScenario,
        private readonly SmokeScenarioSelector $scenarioSelector,
    ) {}

    /**
     * @param  list<string>  $only
     * @return list<ApiContractScenario>
     */
    public function scenarios(array $only): array
    {
        return $this->scenarioSelector->select($this->orderedScenarios(), $only, 'api smoke');
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->scenarioSelector->names($this->orderedScenarios());
    }

    /**
     * @return array<string,ApiContractScenario>
     */
    private function orderedScenarios(): array
    {
        return [
            'catalog' => $this->catalogScenario,
            'cart' => $this->cartScenario,
            'checkout' => $this->checkoutScenario,
            'admin_products' => $this->adminProductsScenario,
            'payment_webhook' => $this->paymentWebhookScenario,
            'shipping_webhook' => $this->shippingWebhookScenario,
        ];
    }
}
