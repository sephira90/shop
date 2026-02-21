<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PerformanceSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure catalog list query-path stays within query budget.
     */
    public function test_catalog_list_query_path_stays_within_budget(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $queryCount = $this->measureQueryCount(function (): void {
            $this->getJson('/api/v1/catalog/products?per_page=12')
                ->assertOk()
                ->assertJsonPath('meta.per_page', 12);
        });

        $this->assertLessThanOrEqual(8, $queryCount, 'Catalog list query budget exceeded.');
    }

    /**
     * Ensure admin orders summary query-path stays within query budget.
     */
    public function test_admin_orders_summary_query_path_stays_within_budget(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        foreach (range(1, 25) as $index) {
            Order::query()->create([
                'order_number' => sprintf('ORD-PERF-%04d', $index),
                'email' => "perf{$index}@example.com",
                'status' => 'pending',
                'payment_status' => 'pending',
                'shipment_status' => 'pending',
                'currency' => 'USD',
                'subtotal' => 100 + $index,
                'discount_total' => 0,
                'shipping_total' => 0,
                'total' => 100 + $index,
                'billing_address' => ['line1' => 'Smoke Street'],
                'shipping_address' => ['line1' => 'Smoke Street'],
                'cart_snapshot' => [],
                'placed_at' => now(),
            ]);
        }

        $queryCount = $this->measureQueryCount(function (): void {
            $this->getJson('/api/v1/admin/orders?per_page=20')
                ->assertOk()
                ->assertJsonPath('meta.per_page', 20);
        });

        $this->assertLessThanOrEqual(6, $queryCount, 'Admin orders summary query budget exceeded.');
    }

    /**
     * Measure query count produced by one callback.
     */
    private function measureQueryCount(callable $callback): int
    {
        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $callback();

        $queryCount = count($connection->getQueryLog());
        $connection->disableQueryLog();

        return $queryCount;
    }
}
