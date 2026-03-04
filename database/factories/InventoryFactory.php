<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariantFactory::new(),
            'quantity' => fake()->numberBetween(10, 200),
            'reserved_quantity' => 0,
            'low_stock_threshold' => 3,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (): array => [
            'quantity' => 2,
            'reserved_quantity' => 1,
            'low_stock_threshold' => 3,
        ]);
    }
}
