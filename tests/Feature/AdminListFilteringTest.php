<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminListFilteringTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure admin orders list applies typed filters.
     */
    public function test_orders_index_applies_query_and_status_filters(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        Order::query()->create([
            'order_number' => 'ORD-100',
            'email' => 'john@example.com',
            'status' => 'pending',
            'payment_status' => 'pending',
            'shipment_status' => 'pending',
            'currency' => 'USD',
            'subtotal' => 100,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100,
            'billing_address' => ['line1' => 'Street 1'],
            'shipping_address' => ['line1' => 'Street 1'],
            'cart_snapshot' => [],
        ]);

        Order::query()->create([
            'order_number' => 'ORD-200',
            'email' => 'jane@example.com',
            'status' => 'completed',
            'payment_status' => 'captured',
            'shipment_status' => 'delivered',
            'currency' => 'USD',
            'subtotal' => 250,
            'discount_total' => 10,
            'shipping_total' => 5,
            'total' => 245,
            'billing_address' => ['line1' => 'Street 2'],
            'shipping_address' => ['line1' => 'Street 2'],
            'cart_snapshot' => [],
        ]);

        $this->getJson('/api/v1/admin/orders?q=jane&status=completed&payment_status=captured&shipment_status=delivered&per_page=5')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'jane@example.com')
            ->assertJsonPath('data.0.order_number', 'ORD-200');
    }

    /**
     * Ensure admin products list applies typed filters.
     */
    public function test_products_index_applies_query_status_and_category_filters(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $phones = Category::query()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $laptops = Category::query()->create([
            'name' => 'Laptops',
            'slug' => 'laptops',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Product::query()->create([
            'sku' => 'PHONE-001',
            'name' => 'Phone One',
            'slug' => 'phone-one',
            'status' => 'active',
            'category_id' => $phones->id,
        ]);

        Product::query()->create([
            'sku' => 'LAPTOP-001',
            'name' => 'Laptop One',
            'slug' => 'laptop-one',
            'status' => 'active',
            'category_id' => $laptops->id,
        ]);

        $this->getJson('/api/v1/admin/products?q=laptop&status=active&category_id='.$laptops->id.'&per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'LAPTOP-001')
            ->assertJsonPath('data.0.name', 'Laptop One');
    }

    /**
     * Ensure admin promotions list applies typed filters.
     */
    public function test_promotions_index_applies_query_and_active_filters(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $activePromotion = Promotion::query()->create([
            'name' => 'Spring Campaign',
            'code' => null,
            'type' => 'percent',
            'value' => 15,
            'is_active' => true,
        ]);

        Coupon::query()->create([
            'promotion_id' => $activePromotion->id,
            'code' => 'VIP-15',
            'is_active' => true,
        ]);

        $inactivePromotion = Promotion::query()->create([
            'name' => 'Legacy Campaign',
            'code' => null,
            'type' => 'percent',
            'value' => 5,
            'is_active' => false,
        ]);

        Coupon::query()->create([
            'promotion_id' => $inactivePromotion->id,
            'code' => 'VIP-OLD',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/admin/promotions?q=vip&is_active=1&per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activePromotion->id)
            ->assertJsonPath('data.0.name', 'Spring Campaign');
    }

    /**
     * Ensure admin promotions list accepts string boolean query values used by browser query strings.
     */
    public function test_promotions_index_accepts_string_boolean_active_filter(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        Promotion::query()->create([
            'name' => 'Visible Campaign',
            'code' => null,
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        Promotion::query()->create([
            'name' => 'Hidden Campaign',
            'code' => null,
            'type' => 'percent',
            'value' => 10,
            'is_active' => false,
        ]);

        $this->getJson('/api/v1/admin/promotions?is_active=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Visible Campaign');
    }

    /**
     * Ensure admin categories list applies typed filters.
     */
    public function test_categories_index_applies_query_and_active_filters(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        Category::query()->create([
            'name' => 'Shoes',
            'slug' => 'shoes',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Category::query()->create([
            'name' => 'Shoes Archive',
            'slug' => 'shoes-archive',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->getJson('/api/v1/admin/categories?q=shoes&is_active=1&per_page=5')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Shoes')
            ->assertJsonPath('data.0.slug', 'shoes');
    }

    /**
     * Ensure admin categories list accepts string boolean query values used by browser query strings.
     */
    public function test_categories_index_accepts_string_boolean_active_filter(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        Category::query()->create([
            'name' => 'Published',
            'slug' => 'published',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Category::query()->create([
            'name' => 'Archived',
            'slug' => 'archived',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->getJson('/api/v1/admin/categories?is_active=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Published');
    }

    /**
     * Ensure invalid filter payload is rejected.
     */
    public function test_orders_index_rejects_invalid_status_filter(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/admin/orders?status=unknown')->assertUnprocessable();
    }

    /**
     * Ensure invalid category filters are rejected.
     */
    public function test_categories_index_rejects_invalid_is_active_filter(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/admin/categories?is_active=invalid')->assertUnprocessable();
    }
}
