<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PerformanceSmokeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_performance_smoke_command_can_run_selected_scenario_subset(): void
    {
        $exitCode = Artisan::call('app:performance-smoke', [
            '--only' => 'admin_orders_summary',
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('admin_orders_summary', $output);
        $this->assertStringNotContainsString('catalog_list_cold', $output);
        $this->assertStringContainsString('Performance smoke checks passed.', $output);
    }

    public function test_performance_smoke_command_aggregates_budget_violations(): void
    {
        $this->artisanCommand('app:performance-smoke', [
            '--only' => 'admin_orders_summary,admin_products_list',
            '--max-orders-ms' => '999999',
            '--max-orders-queries' => '0',
            '--max-admin-products-ms' => '999999',
            '--max-admin-products-queries' => '0',
        ])
            ->assertFailed()
            ->expectsOutputToContain('admin_orders_summary query budget exceeded')
            ->expectsOutputToContain('admin_products_list query budget exceeded');
    }

    public function test_performance_smoke_command_rolls_back_data_in_production_by_default(): void
    {
        config()->set('app.env', 'production');

        $this->artisanCommand('app:performance-smoke', [
            '--only' => 'checkout_place_order',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Production safeguard: smoke data rolled back.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_performance_smoke_command_fails_for_unknown_selected_scenario(): void
    {
        $this->artisanCommand('app:performance-smoke --only=missing_scenario')
            ->assertFailed()
            ->expectsOutputToContain('Performance smoke failed: Option --only contains unknown performance smoke scenario "missing_scenario".');
    }
}
