<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Smoke\Performance\PerformanceSmokeOptionsResolver;
use App\Support\Smoke\SmokeExecutionOptionsResolver;
use InvalidArgumentException;
use Tests\TestCase;

class PerformanceSmokeOptionsResolverTest extends TestCase
{
    public function test_resolve_parses_selected_scenarios_and_budgets(): void
    {
        $resolver = new PerformanceSmokeOptionsResolver(new SmokeExecutionOptionsResolver);

        $options = $resolver->resolve([
            'persist' => true,
            'only' => 'cart_show,admin_orders_summary',
            'max_catalog_ms' => '1200',
            'max_catalog_queries' => '12',
            'max_catalog_warm_ms' => '600',
            'max_catalog_warm_queries' => '4',
            'max_cart_ms' => '600',
            'max_cart_queries' => '8',
            'max_checkout_ms' => '1800',
            'max_checkout_queries' => '40',
            'max_orders_ms' => '800',
            'max_orders_queries' => '6',
            'max_admin_products_ms' => '900',
            'max_admin_products_queries' => '12',
        ]);

        $this->assertTrue($options->execution->persist);
        $this->assertSame(['cart_show', 'admin_orders_summary'], $options->execution->onlyScenarios);
        $this->assertSame(8, $options->budgetFor('cart_show')->maxQueries);
        $this->assertSame(800.0, $options->budgetFor('admin_orders_summary')->maxMs);
    }

    public function test_resolve_rejects_negative_budget(): void
    {
        $resolver = new PerformanceSmokeOptionsResolver(new SmokeExecutionOptionsResolver);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option --max-cart-ms must be >= 0.');

        $resolver->resolve([
            'persist' => false,
            'only' => '',
            'max_catalog_ms' => '1200',
            'max_catalog_queries' => '12',
            'max_catalog_warm_ms' => '600',
            'max_catalog_warm_queries' => '4',
            'max_cart_ms' => '-1',
            'max_cart_queries' => '8',
            'max_checkout_ms' => '1800',
            'max_checkout_queries' => '40',
            'max_orders_ms' => '800',
            'max_orders_queries' => '6',
            'max_admin_products_ms' => '900',
            'max_admin_products_queries' => '12',
        ]);
    }
}
