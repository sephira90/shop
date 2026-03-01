<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Data\TypedValue;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     * Ensure cart show query-path stays within query budget.
     */
    public function test_cart_show_query_path_stays_within_budget(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $variantId = $this->resolveAvailableVariantId();
        $guestToken = 'perf-cart-'.Str::lower(Str::random(12));

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variantId,
            'quantity' => 1,
            'guest_token' => $guestToken,
        ])->assertOk();

        $queryCount = $this->measureQueryCount(function () use ($guestToken): void {
            $this->getJson('/api/v1/cart?guest_token='.$guestToken)
                ->assertOk()
                ->assertJsonPath('data.items.0.quantity', 1);
        });

        $this->assertLessThanOrEqual(8, $queryCount, 'Cart show query budget exceeded.');
    }

    /**
     * Ensure checkout place-order query-path stays within query budget.
     */
    public function test_checkout_place_order_query_path_stays_within_budget(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $variantId = $this->resolveAvailableVariantId();
        $guestToken = 'perf-checkout-'.Str::lower(Str::random(12));

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variantId,
            'quantity' => 1,
            'guest_token' => $guestToken,
        ])->assertOk();

        $queryCount = $this->measureQueryCount(function () use ($guestToken): void {
            $this->postJson(
                '/api/v1/checkout/place-order',
                [
                    'guest_token' => $guestToken,
                    'email' => 'performance-smoke@example.com',
                    'billing_address' => [
                        'line1' => '1 Performance Street',
                        'city' => 'New York',
                        'country' => 'US',
                        'postcode' => '10001',
                    ],
                    'shipping_address' => [
                        'line1' => '1 Performance Street',
                        'city' => 'New York',
                        'country' => 'US',
                        'postcode' => '10001',
                    ],
                ],
                [
                    'Idempotency-Key' => 'perf-checkout-'.Str::lower(Str::random(16)),
                ],
            )
                ->assertCreated()
                ->assertJsonPath('data.email', 'performance-smoke@example.com');
        });

        $this->assertLessThanOrEqual(40, $queryCount, 'Checkout place-order query budget exceeded.');
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
     * Ensure admin products list query-path stays within query budget.
     */
    public function test_admin_products_list_query_path_stays_within_budget(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $queryCount = $this->measureQueryCount(function (): void {
            $this->getJson('/api/v1/admin/products?per_page=20')
                ->assertOk()
                ->assertJsonPath('meta.per_page', 20);
        });

        $this->assertLessThanOrEqual(12, $queryCount, 'Admin products list query budget exceeded.');
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

    /**
     * Resolve one active and in-stock variant id for checkout/cart budgets.
     */
    private function resolveAvailableVariantId(): int
    {
        $variantId = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', static function ($productQuery): void {
                $productQuery
                    ->where('status', ProductStatus::ACTIVE->value)
                    ->whereNotNull('published_at');
            })
            ->whereHas('inventory', static function ($inventoryQuery): void {
                $inventoryQuery->whereColumn('quantity', '>', 'reserved_quantity');
            })
            ->orderBy('id')
            ->value('id');

        $this->assertNotNull($variantId, 'Performance budget precondition failed: no available variant found.');

        return TypedValue::int($variantId);
    }
}
