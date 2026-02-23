<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookFlowSmokeCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure webhook flow smoke command validates end-to-end chain.
     */
    public function test_webhook_flow_smoke_command_passes(): void
    {
        $this->artisan('app:webhook-flow-smoke')
            ->assertSuccessful()
            ->expectsOutputToContain('Webhook flow smoke checks passed.');

        $order = Order::query()->latest('id')->first();
        $shipment = Shipment::query()->latest('id')->first();

        $this->assertInstanceOf(Order::class, $order);
        $this->assertInstanceOf(Shipment::class, $shipment);
        $this->assertSame('completed', (string) $order->getRawOriginal('status'));
        $this->assertSame('captured', (string) $order->getRawOriginal('payment_status'));
        $this->assertSame('delivered', (string) $order->getRawOriginal('shipment_status'));
        $this->assertSame('delivered', (string) $shipment->getRawOriginal('status'));
    }

    /**
     * Ensure production execution does not persist smoke data by default.
     */
    public function test_webhook_flow_smoke_command_rolls_back_data_in_production(): void
    {
        config()->set('app.env', 'production');

        $this->artisan('app:webhook-flow-smoke')
            ->assertSuccessful()
            ->expectsOutputToContain('Production safeguard: smoke data rolled back.');

        $this->assertDatabaseMissing('users', ['email' => 'smoke.user@shop.local']);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('shipments', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * Ensure webhook smoke flow skips variants without available inventory.
     */
    public function test_webhook_flow_smoke_command_uses_variant_with_available_inventory(): void
    {
        $category = Category::query()->create([
            'name' => 'Smoke Category',
            'slug' => 'smoke-webhook-category',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'sku' => 'SMOKE-WEBHOOK-001',
            'name' => 'Smoke Webhook Product',
            'slug' => 'smoke-webhook-product',
            'status' => ProductStatus::ACTIVE->value,
            'category_id' => $category->id,
            'published_at' => now(),
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SMOKE-WEBHOOK-001-V1',
            'name' => 'Out of Stock Variant',
            'price' => 10,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        Inventory::query()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 0,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 1,
        ]);

        $this->artisan('app:webhook-flow-smoke')
            ->assertSuccessful()
            ->expectsOutputToContain('Webhook flow smoke checks passed.');
    }
}
