<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 999);

        return [
            'product_id' => ProductFactory::new(),
            'sku' => 'VAR-'.strtoupper(Str::random(8)),
            'name' => ucfirst(fake()->words(2, true)),
            'attributes' => [
                'size' => fake()->randomElement(['S', 'M', 'L']),
                'color' => fake()->safeColorName(),
            ],
            'price' => $price,
            'compare_at_price' => round($price + fake()->randomFloat(2, 1, 50), 2),
            'currency' => 'USD',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
