<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Cart\CartResultMapper;
use App\Support\Data\TypedValue;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CartResultMapperTest extends TestCase
{
    /**
     * Ensure cart mapper builds deterministic DTO payload.
     */
    public function test_to_result_dto_maps_items_and_summary(): void
    {
        $cart = new Cart([
            'guest_token' => 'guest-1',
            'currency' => 'USD',
            'status' => CartStatus::ACTIVE->value,
        ]);
        $cart->setAttribute('id', 'cart-1');

        $variant = new ProductVariant([
            'sku' => 'SKU-1',
            'name' => 'Variant 1',
        ]);

        $itemWithVariant = new CartItem([
            'product_variant_id' => 10,
            'quantity' => 2,
            'unit_price' => 12.5,
            'line_total' => 25.0,
        ]);
        $itemWithVariant->setRelation('variant', $variant);

        $itemWithoutVariant = new CartItem([
            'product_variant_id' => 20,
            'quantity' => 1,
            'unit_price' => 5.0,
            'line_total' => 5.0,
        ]);
        $itemWithoutVariant->setRelation('variant', null);

        $cart->setRelation('items', new Collection([$itemWithVariant, $itemWithoutVariant]));

        /** @var array{id:string,guest_token:?string,status:string,summary:array{subtotal:float,total:float},items:list<array{sku:string,name:string}>} $result */
        $result = TypedValue::associativeArray((new CartResultMapper)->toResultDto($cart)->toArray());

        self::assertSame('cart-1', $result['id']);
        self::assertSame('guest-1', $result['guest_token']);
        self::assertSame('active', $result['status']);
        self::assertSame(30.0, $result['summary']['subtotal']);
        self::assertCount(2, $result['items']);
        self::assertSame('SKU-1', $result['items'][0]['sku']);
        self::assertSame('Variant 1', $result['items'][0]['name']);
        self::assertSame('', $result['items'][1]['sku']);
        self::assertSame('', $result['items'][1]['name']);
    }

    /**
     * Ensure empty cart items produce an empty DTO list and zero summary.
     */
    public function test_to_result_dto_handles_empty_item_collection(): void
    {
        $cart = new Cart([
            'currency' => 'USD',
            'status' => CartStatus::ACTIVE->value,
        ]);
        $cart->setAttribute('id', 'cart-2');
        $cart->setRelation('items', new Collection([]));

        /** @var array{id:string,items:list<array<string, mixed>>,summary:array{subtotal:float,total:float}} $result */
        $result = TypedValue::associativeArray((new CartResultMapper)->toResultDto($cart)->toArray());

        self::assertSame('cart-2', $result['id']);
        self::assertSame([], $result['items']);
        self::assertSame(0.0, $result['summary']['subtotal']);
        self::assertSame(0.0, $result['summary']['total']);
    }

    /**
     * Ensure mapper subtotal aggregation keeps cent precision for decimal line totals.
     */
    public function test_to_result_dto_aggregates_subtotal_with_money_precision(): void
    {
        $cart = new Cart([
            'currency' => 'USD',
            'status' => CartStatus::ACTIVE->value,
        ]);
        $cart->setAttribute('id', 'cart-3');

        $firstItem = new CartItem([
            'product_variant_id' => 10,
            'quantity' => 1,
            'unit_price' => 0.1,
            'line_total' => 0.1,
        ]);
        $firstItem->setRelation('variant', null);

        $secondItem = new CartItem([
            'product_variant_id' => 20,
            'quantity' => 1,
            'unit_price' => 0.2,
            'line_total' => 0.2,
        ]);
        $secondItem->setRelation('variant', null);

        $cart->setRelation('items', new Collection([$firstItem, $secondItem]));

        /** @var array{summary:array{subtotal:float,total:float}} $result */
        $result = TypedValue::associativeArray((new CartResultMapper)->toResultDto($cart)->toArray());

        self::assertSame(0.3, $result['summary']['subtotal']);
        self::assertSame(0.3, $result['summary']['total']);
    }
}
