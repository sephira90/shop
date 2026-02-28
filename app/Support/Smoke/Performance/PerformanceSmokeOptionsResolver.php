<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance;

use App\Support\Smoke\Performance\Dto\PerformanceSmokeBudgetDto;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeOptionsDto;
use App\Support\Smoke\SmokeExecutionOptionsResolver;
use InvalidArgumentException;

final class PerformanceSmokeOptionsResolver
{
    public function __construct(
        private readonly SmokeExecutionOptionsResolver $executionOptionsResolver,
    ) {}

    /**
     * @param  array{
     *     persist:mixed,
     *     only:mixed,
     *     max_catalog_ms:mixed,
     *     max_catalog_queries:mixed,
     *     max_catalog_warm_ms:mixed,
     *     max_catalog_warm_queries:mixed,
     *     max_cart_ms:mixed,
     *     max_cart_queries:mixed,
     *     max_checkout_ms:mixed,
     *     max_checkout_queries:mixed,
     *     max_orders_ms:mixed,
     *     max_orders_queries:mixed,
     *     max_admin_products_ms:mixed,
     *     max_admin_products_queries:mixed
     * }  $options
     */
    public function resolve(array $options): PerformanceSmokeOptionsDto
    {
        return new PerformanceSmokeOptionsDto(
            execution: $this->executionOptionsResolver->resolve([
                'persist' => $options['persist'],
                'only' => $options['only'],
            ]),
            budgets: [
                'catalog_list_cold' => new PerformanceSmokeBudgetDto(
                    scenario: 'catalog_list_cold',
                    maxMs: $this->resolveFloatBudget($options['max_catalog_ms'], 'max-catalog-ms'),
                    maxQueries: $this->resolveIntBudget($options['max_catalog_queries'], 'max-catalog-queries'),
                ),
                'catalog_list_warm' => new PerformanceSmokeBudgetDto(
                    scenario: 'catalog_list_warm',
                    maxMs: $this->resolveFloatBudget($options['max_catalog_warm_ms'], 'max-catalog-warm-ms'),
                    maxQueries: $this->resolveIntBudget($options['max_catalog_warm_queries'], 'max-catalog-warm-queries'),
                ),
                'cart_show' => new PerformanceSmokeBudgetDto(
                    scenario: 'cart_show',
                    maxMs: $this->resolveFloatBudget($options['max_cart_ms'], 'max-cart-ms'),
                    maxQueries: $this->resolveIntBudget($options['max_cart_queries'], 'max-cart-queries'),
                ),
                'checkout_place_order' => new PerformanceSmokeBudgetDto(
                    scenario: 'checkout_place_order',
                    maxMs: $this->resolveFloatBudget($options['max_checkout_ms'], 'max-checkout-ms'),
                    maxQueries: $this->resolveIntBudget($options['max_checkout_queries'], 'max-checkout-queries'),
                ),
                'admin_orders_summary' => new PerformanceSmokeBudgetDto(
                    scenario: 'admin_orders_summary',
                    maxMs: $this->resolveFloatBudget($options['max_orders_ms'], 'max-orders-ms'),
                    maxQueries: $this->resolveIntBudget($options['max_orders_queries'], 'max-orders-queries'),
                ),
                'admin_products_list' => new PerformanceSmokeBudgetDto(
                    scenario: 'admin_products_list',
                    maxMs: $this->resolveFloatBudget($options['max_admin_products_ms'], 'max-admin-products-ms'),
                    maxQueries: $this->resolveIntBudget($options['max_admin_products_queries'], 'max-admin-products-queries'),
                ),
            ],
        );
    }

    private function resolveFloatBudget(mixed $raw, string $name): float
    {
        if (! is_numeric($raw)) {
            throw new InvalidArgumentException(sprintf('Option --%s must be a non-negative number.', $name));
        }

        $value = (float) $raw;

        if ($value < 0) {
            throw new InvalidArgumentException(sprintf('Option --%s must be >= 0.', $name));
        }

        return $value;
    }

    private function resolveIntBudget(mixed $raw, string $name): int
    {
        if (! is_numeric($raw)) {
            throw new InvalidArgumentException(sprintf('Option --%s must be a non-negative integer.', $name));
        }

        $value = (int) $raw;

        if ($value < 0) {
            throw new InvalidArgumentException(sprintf('Option --%s must be >= 0.', $name));
        }

        return $value;
    }
}
