<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CartStatus;
use App\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'guest_token' => 'guest-'.Str::lower(Str::random(24)),
            'currency' => 'USD',
            'status' => CartStatus::ACTIVE->value,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function checkedOut(): static
    {
        return $this->state(fn (): array => [
            'status' => CartStatus::CHECKED_OUT->value,
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(fn (): array => [
            'status' => CartStatus::ABANDONED->value,
        ]);
    }
}
