<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Support\Data\TypedValue;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesCatalogVariant;
use Tests\TestCase;

class PhaseOneHardeningTest extends TestCase
{
    use CreatesCatalogVariant;
    use RefreshDatabase;

    /**
     * Ensure unpublished product cannot be fetched by storefront slug endpoint.
     */
    public function test_catalog_show_does_not_return_unpublished_product(): void
    {
        $variant = $this->createActiveVariantWithInventory();
        $product = $variant->product()->firstOrFail();
        $product->update(['published_at' => null]);

        $this->getJson('/api/v1/catalog/products/'.$product->slug)
            ->assertNotFound()
            ->assertJsonPath('error.message', 'Product not found.');
    }

    /**
     * Ensure storefront payload includes only active variants.
     */
    public function test_catalog_show_returns_only_active_variants(): void
    {
        $product = $this->createActiveProductWithVariants([9.99, 19.99]);
        $variant = $product->variants->first();
        $this->assertNotNull($variant);
        $variant->update(['is_active' => false]);

        $response = $this->getJson('/api/v1/catalog/products/'.$product->slug)
            ->assertOk();

        $variants = $this->jsonArrayList($response, 'data.variants');
        $this->assertCount(1, $variants);
        $this->assertSame(true, $variants[0]['is_active']);
    }

    /**
     * Ensure cart rejects inactive variants.
     */
    public function test_cart_rejects_inactive_variant(): void
    {
        $variant = $this->createActiveVariantWithInventory();
        $variant->update(['is_active' => false]);

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'guest_token' => 'phase-one-inactive-variant',
        ])->assertUnprocessable()
            ->assertJsonPath('error.message', 'Selected variant is not available.');
    }

    /**
     * Ensure cart rejects variants from unpublished products.
     */
    public function test_cart_rejects_variant_from_unpublished_product(): void
    {
        $variant = $this->createActiveVariantWithInventory();
        $variant->product()->update(['published_at' => null]);

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'guest_token' => 'phase-one-unpublished-product',
        ])->assertUnprocessable()
            ->assertJsonPath('error.message', 'Selected variant is not available.');
    }

    /**
     * Ensure checkout does not allow orders with unavailable variants already in cart.
     */
    public function test_checkout_rejects_cart_with_unavailable_variant(): void
    {
        $variant = $this->createActiveVariantWithInventory();
        $guestToken = 'phase-one-checkout-unavailable';

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'guest_token' => $guestToken,
        ])->assertOk();

        $variant->update(['is_active' => false]);

        $this->withHeader('Idempotency-Key', 'phase-one-checkout-unavailable')
            ->postJson('/api/v1/checkout/place-order', [
                'guest_token' => $guestToken,
                'email' => 'guest@example.com',
                'billing_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
                'shipping_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Cart contains unavailable items.');
    }

    /**
     * Ensure cancelled_at timestamp is preserved after status changes.
     */
    public function test_admin_order_status_update_does_not_clear_existing_cancelled_at(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $cancelledAt = now()->subDay()->startOfSecond();

        $order = Order::query()->create([
            'order_number' => 'ORD-PHASE1-KEEP-CANCELLED',
            'email' => 'customer@example.com',
            'status' => 'cancelled',
            'payment_status' => 'pending',
            'shipment_status' => 'pending',
            'currency' => 'USD',
            'subtotal' => 100,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100,
            'billing_address' => ['line1' => '1 Main Street', 'city' => 'New York', 'country' => 'US', 'postcode' => '10001'],
            'shipping_address' => ['line1' => '1 Main Street', 'city' => 'New York', 'country' => 'US', 'postcode' => '10001'],
            'cart_snapshot' => [],
            'placed_at' => now(),
            'cancelled_at' => $cancelledAt,
        ]);

        $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', [
            'status' => 'processing',
        ])->assertOk();

        $freshOrder = Order::query()->findOrFail($order->id);

        $this->assertSame('processing', TypedValue::string($freshOrder->getRawOriginal('status')));
        $this->assertNotNull($freshOrder->getRawOriginal('cancelled_at'));
        $this->assertSame($cancelledAt->toDateTimeString(), TypedValue::string($freshOrder->getRawOriginal('cancelled_at')));
    }

    /**
     * Ensure admin status update derives order status from payment/shipment transitions.
     */
    public function test_admin_order_status_update_derives_order_status_when_status_not_provided(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $order = Order::query()->create([
            'order_number' => 'ORD-PHASE1-DERIVE-STATUS',
            'email' => 'customer@example.com',
            'status' => 'pending',
            'payment_status' => 'pending',
            'shipment_status' => 'pending',
            'currency' => 'USD',
            'subtotal' => 100,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100,
            'billing_address' => ['line1' => '1 Main Street', 'city' => 'New York', 'country' => 'US', 'postcode' => '10001'],
            'shipping_address' => ['line1' => '1 Main Street', 'city' => 'New York', 'country' => 'US', 'postcode' => '10001'],
            'cart_snapshot' => [],
            'placed_at' => now(),
        ]);

        $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', [
            'payment_status' => 'captured',
        ])->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.payment_status', 'captured');

        $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', [
            'shipment_status' => 'delivered',
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.shipment_status', 'delivered');
    }

    /**
     * Ensure invalid admin payment status transition is rejected.
     */
    public function test_admin_order_status_update_rejects_invalid_payment_transition(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $order = Order::query()->create([
            'order_number' => 'ORD-PHASE1-INVALID-PAYMENT-TRANSITION',
            'email' => 'customer@example.com',
            'status' => 'paid',
            'payment_status' => 'captured',
            'shipment_status' => 'pending',
            'currency' => 'USD',
            'subtotal' => 100,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100,
            'billing_address' => ['line1' => '1 Main Street', 'city' => 'New York', 'country' => 'US', 'postcode' => '10001'],
            'shipping_address' => ['line1' => '1 Main Street', 'city' => 'New York', 'country' => 'US', 'postcode' => '10001'],
            'cart_snapshot' => [],
            'placed_at' => now(),
        ]);

        $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', [
            'payment_status' => 'authorized',
        ])->assertUnprocessable()
            ->assertJsonPath('error.message', 'Payment status transition is not allowed.');

        $freshOrder = Order::query()->findOrFail($order->id);
        $this->assertSame('paid', TypedValue::string($freshOrder->getRawOriginal('status')));
        $this->assertSame('captured', TypedValue::string($freshOrder->getRawOriginal('payment_status')));
    }

    /**
     * Ensure invalid direct admin order status transition is rejected.
     */
    public function test_admin_order_status_update_rejects_invalid_direct_order_transition(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $order = Order::query()->create([
            'order_number' => 'ORD-PHASE1-INVALID-ORDER-TRANSITION',
            'email' => 'customer@example.com',
            'status' => 'cancelled',
            'payment_status' => 'pending',
            'shipment_status' => 'pending',
            'currency' => 'USD',
            'subtotal' => 100,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100,
            'billing_address' => ['line1' => '1 Main Street', 'city' => 'New York', 'country' => 'US', 'postcode' => '10001'],
            'shipping_address' => ['line1' => '1 Main Street', 'city' => 'New York', 'country' => 'US', 'postcode' => '10001'],
            'cart_snapshot' => [],
            'placed_at' => now(),
            'cancelled_at' => now()->subHour(),
        ]);

        $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', [
            'status' => 'paid',
        ])->assertUnprocessable()
            ->assertJsonPath('error.message', 'Order status transition is not allowed.');

        $freshOrder = Order::query()->findOrFail($order->id);
        $this->assertSame('cancelled', TypedValue::string($freshOrder->getRawOriginal('status')));
    }
}
