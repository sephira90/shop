<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PromotionType;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->words(3, true));

        return [
            'name' => $name,
            'code' => 'PROMO-'.strtoupper(Str::random(6)),
            'type' => PromotionType::PERCENT->value,
            'value' => 10.0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'usage_limit' => null,
            'usage_count' => 0,
        ];
    }

    public function fixedAmount(float $amount = 10.0): static
    {
        return $this->state(fn (): array => [
            'type' => PromotionType::FIXED->value,
            'value' => $amount,
        ]);
    }
}
