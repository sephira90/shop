<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 4);
        $unitPrice = fake()->randomFloat(2, 10, 300);

        return [
            'order_id' => OrderFactory::new(),
            'product_variant_id' => ProductVariantFactory::new(),
            'sku' => 'ORD-ITEM-'.strtoupper(Str::random(8)),
            'name' => ucfirst(fake()->words(2, true)),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($unitPrice * $quantity, 2),
            'meta' => ['source' => 'factory'],
        ];
    }
}
