<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Models\Coupon;
use App\Models\Promotion;
use Tests\TestCase;

class PromotionCounterFillableGuardrailTest extends TestCase
{
    public function test_promotion_usage_count_is_not_mass_assignable(): void
    {
        $this->assertNotContains(
            'usage_count',
            (new Promotion)->getFillable(),
            'Promotion::usage_count must stay outside $fillable and mutate only through explicit counter operations.'
        );
    }

    public function test_coupon_redeemed_count_is_not_mass_assignable(): void
    {
        $this->assertNotContains(
            'redeemed_count',
            (new Coupon)->getFillable(),
            'Coupon::redeemed_count must stay outside $fillable and mutate only through explicit counter operations.'
        );
    }
}
