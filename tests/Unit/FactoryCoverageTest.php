<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use Database\Factories\CartFactory;
use Database\Factories\CartItemFactory;
use Database\Factories\CouponFactory;
use Database\Factories\InventoryFactory;
use Database\Factories\OrderFactory;
use Database\Factories\OrderItemFactory;
use Database\Factories\PriceFactory;
use Database\Factories\ProductFactory;
use Database\Factories\ProductVariantFactory;
use Database\Factories\PromotionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_factories_create_variant_inventory_and_price_without_seeders(): void
    {
        $product = ProductFactory::new()->createOne();
        $variant = ProductVariantFactory::new()->createOne([
            'product_id' => $product->id,
            'price' => 149.99,
            'currency' => 'USD',
        ]);
        $inventory = InventoryFactory::new()->createOne([
            'product_variant_id' => $variant->id,
            'quantity' => 120,
        ]);
        $price = PriceFactory::new()->createOne([
            'product_variant_id' => $variant->id,
            'amount' => 149.99,
            'currency' => 'USD',
        ]);

        $this->assertInstanceOf(ProductVariant::class, $variant->fresh());
        $this->assertSame($product->id, $variant->product_id);
        $this->assertSame($variant->id, $inventory->product_variant_id);
        $this->assertSame($variant->id, $price->product_variant_id);
    }

    public function test_cart_and_order_factories_create_item_relationships_without_seeders(): void
    {
        $variant = ProductVariantFactory::new()->createOne();
        InventoryFactory::new()->createOne([
            'product_variant_id' => $variant->id,
            'quantity' => 50,
        ]);

        $cart = CartFactory::new()->createOne();
        $cartItem = CartItemFactory::new()->createOne([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'unit_price' => 20.00,
            'line_total' => 60.00,
        ]);

        $order = OrderFactory::new()->createOne([
            'subtotal' => 60.00,
            'total' => 60.00,
        ]);
        $orderItem = OrderItemFactory::new()->createOne([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'unit_price' => 20.00,
            'total_price' => 60.00,
        ]);

        $this->assertInstanceOf(Cart::class, $cart->fresh());
        $this->assertSame($cart->id, $cartItem->cart_id);
        $this->assertInstanceOf(Order::class, $order->fresh());
        $this->assertSame($order->id, $orderItem->order_id);
    }

    public function test_promotion_and_coupon_factories_create_coupon_relationship_without_seeders(): void
    {
        $promotion = PromotionFactory::new()->fixedAmount(25.0)->createOne();
        $coupon = CouponFactory::new()->createOne([
            'promotion_id' => $promotion->id,
        ]);

        $this->assertInstanceOf(Coupon::class, $coupon->fresh());
        $this->assertSame($promotion->id, $coupon->promotion_id);
        $this->assertTrue($coupon->is_active);
    }
}
