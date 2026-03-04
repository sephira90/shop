<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Cart\CartMutationService;
use Database\Factories\InventoryFactory;
use Database\Factories\ProductFactory;
use Database\Factories\ProductVariantFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartMutationSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsert_item_reloads_locked_cart_state_before_mutation(): void
    {
        $product = ProductFactory::new()->createOne();
        $variant = ProductVariantFactory::new()->createOne([
            'product_id' => $product->id,
            'price' => 99.99,
        ]);
        InventoryFactory::new()->createOne([
            'product_variant_id' => $variant->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $cart = Cart::query()->create([
            'guest_token' => 'cart-mutation-safety-token',
            'currency' => 'USD',
            'status' => CartStatus::ACTIVE->value,
        ]);

        $createdItem = CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => $variant->price,
            'line_total' => bcmul((string) $variant->price, '1', 2),
        ]);

        $staleCart = Cart::query()
            ->with(['items.variant.inventory'])
            ->findOrFail($cart->id);

        $createdItem->update([
            'quantity' => 4,
            'line_total' => bcmul((string) $variant->price, '4', 2),
        ]);

        $result = app(CartMutationService::class)->upsertItem($staleCart, $variant->id, 5);

        $freshItem = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();

        $this->assertSame(1, CartItem::query()->where('cart_id', $cart->id)->count());
        $this->assertSame(5, $freshItem->quantity);
        $this->assertSame(bcmul((string) $variant->price, '5', 2), (string) $freshItem->line_total);
        $this->assertSame(5, $result->items->firstWhere('product_variant_id', $variant->id)?->quantity);
    }
}
