<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Price>
 */
class PriceFactory extends Factory
{
    protected $model = Price::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariantFactory::new(),
            'amount' => fake()->randomFloat(2, 10, 999),
            'currency' => 'USD',
            'starts_at' => null,
            'ends_at' => null,
        ];
    }
}
