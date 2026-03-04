<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20, 500);
        $discountTotal = fake()->randomFloat(2, 0, 20);
        $shippingTotal = fake()->randomFloat(2, 0, 15);
        $total = round($subtotal - $discountTotal + $shippingTotal, 2);

        return [
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
            'user_id' => null,
            'email' => fake()->safeEmail(),
            'status' => OrderStatus::PENDING->value,
            'payment_status' => PaymentStatus::PENDING->value,
            'shipment_status' => ShipmentStatus::PENDING->value,
            'currency' => 'USD',
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'shipping_total' => $shippingTotal,
            'total' => $total,
            'billing_address' => [
                'line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country' => fake()->countryCode(),
                'postcode' => fake()->postcode(),
            ],
            'shipping_address' => [
                'line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country' => fake()->countryCode(),
                'postcode' => fake()->postcode(),
            ],
            'cart_snapshot' => ['items' => []],
            'placed_at' => now(),
            'cancelled_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::PAID->value,
            'payment_status' => PaymentStatus::CAPTURED->value,
        ]);
    }
}
