<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Exceptions\CartException;
use App\Domain\ValueObjects\Money;
use App\Domains\Cart\Contracts\CartMutationServiceInterface;
use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
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

        $result = app(CartMutationServiceInterface::class)->upsertItem($staleCart, $variant->id, 5);

        $freshItem = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();
        $expectedLineTotal = Money::fromDecimal((float) $variant->price, (string) $variant->currency)
            ->multiply(5)
            ->toDecimalString();

        $this->assertSame(1, CartItem::query()->where('cart_id', $cart->id)->count());
        $this->assertSame(5, $freshItem->quantity);
        $this->assertSame($expectedLineTotal, (string) $freshItem->line_total);
        $this->assertSame(5, $result->items->firstWhere('product_variant_id', $variant->id)?->quantity);
    }

    public function test_remove_item_rejects_unknown_variant_id(): void
    {
        $this->deleteJson('/api/v1/cart/items/999999?guest_token=cart-remove-unknown-variant')
            ->assertUnprocessable()
            ->assertJsonPath('error.validation.variant_id.0', 'The selected variant id is invalid.');
    }

    public function test_guest_cart_mutation_requires_guest_token(): void
    {
        $product = ProductFactory::new()->createOne();
        $variant = ProductVariantFactory::new()->createOne([
            'product_id' => $product->id,
            'price' => 49.99,
        ]);
        InventoryFactory::new()->createOne([
            'product_variant_id' => $variant->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
        ]);

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertForbidden();
    }

    public function test_upsert_item_rejects_mismatched_authenticated_cart_owner_context(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $cart = Cart::query()->create([
            'user_id' => $owner->id,
            'currency' => 'USD',
            'status' => CartStatus::ACTIVE->value,
        ]);

        $this->expectException(CartException::class);
        $this->expectExceptionMessage('Cart ownership mismatch.');

        app(CartMutationServiceInterface::class)->upsertItem(
            $cart,
            1,
            1,
            $anotherUser,
            null,
        );
    }

    public function test_remove_item_rejects_mismatched_guest_cart_token_context(): void
    {
        $cart = Cart::query()->create([
            'guest_token' => 'guest-owner-token',
            'currency' => 'USD',
            'status' => CartStatus::ACTIVE->value,
        ]);

        $this->expectException(CartException::class);
        $this->expectExceptionMessage('Cart ownership mismatch.');

        app(CartMutationServiceInterface::class)->removeItem(
            $cart,
            1,
            null,
            'another-guest-token',
        );
    }
}
