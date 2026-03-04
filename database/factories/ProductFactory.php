<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));
        $skuSuffix = strtoupper(Str::random(6));

        return [
            'sku' => 'PRD-'.$skuSuffix,
            'name' => $name,
            'slug' => Str::slug($name).'-'.strtolower(Str::random(6)),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => ProductStatus::ACTIVE->value,
            'is_featured' => false,
            'category_id' => CategoryFactory::new(),
            'brand' => fake()->company(),
            'weight_grams' => fake()->numberBetween(100, 5000),
            'meta_title' => $name,
            'meta_description' => fake()->sentence(),
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => ProductStatus::DRAFT->value,
            'published_at' => null,
        ]);
    }
}
