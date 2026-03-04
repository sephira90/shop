<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promotion_id' => PromotionFactory::new(),
            'code' => 'COUPON-'.strtoupper(Str::random(8)),
            'is_active' => true,
            'max_redemptions' => null,
            'redeemed_count' => 0,
            'expires_at' => now()->addDays(14),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
