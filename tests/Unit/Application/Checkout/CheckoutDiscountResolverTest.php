<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Checkout;

use App\Domain\Exceptions\CheckoutException;
use App\Domain\ValueObjects\Money;
use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderInputDto;
use App\Domains\Checkout\Services\CheckoutDiscountResolver;
use App\Domains\Checkout\Services\Dto\CheckoutDiscountContextDto;
use App\Enums\PromotionType;
use App\Models\Coupon;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Verifies CheckoutDiscountResolver consumes the decimal promotion value as an
 * exact string (no float cast) and applies the discount through Money's
 * string-rate arithmetic. Locks the R2 contract: the resolver signature must
 * stay statically typed (no PromotionType|string union, no float promotionValue).
 */
class CheckoutDiscountResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_percent_promotion_applies_exact_decimal_string_rate_without_float_cast(): void
    {
        $now = Carbon::parse('2026-07-04 12:00:00');
        Carbon::setTestNow($now);
        try {
            $promotion = Promotion::query()->create([
                'name' => 'Save ten percent',
                'code' => 'PROMO10',
                'type' => PromotionType::PERCENT->value,
                'value' => 10,
                'is_active' => true,
                'starts_at' => $now->copy()->subHour(),
                'ends_at' => $now->copy()->addHour(),
            ]);

            Coupon::query()->create([
                'promotion_id' => $promotion->id,
                'code' => 'SAVE10',
                'is_active' => true,
            ]);

            $subtotal = Money::fromDecimal('99.99', 'USD');

            $context = app(CheckoutDiscountResolver::class)
                ->resolve($this->buildInput('save10'), $subtotal);

            $this->assertInstanceOf(CheckoutDiscountContextDto::class, $context);
            $this->assertSame(1000, $context->discountTotal->amountCents());
            $this->assertSame('10.00', $context->discountTotal->toDecimalString());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_fixed_promotion_applies_exact_decimal_string_value_without_float_cast(): void
    {
        $now = Carbon::parse('2026-07-04 12:00:00');
        Carbon::setTestNow($now);
        try {
            $promotion = Promotion::query()->create([
                'name' => 'Five off',
                'code' => 'PROMO5FIXED',
                'type' => PromotionType::FIXED->value,
                'value' => 5.50,
                'is_active' => true,
                'starts_at' => $now->copy()->subHour(),
                'ends_at' => $now->copy()->addHour(),
            ]);

            Coupon::query()->create([
                'promotion_id' => $promotion->id,
                'code' => 'SAVE5',
                'is_active' => true,
            ]);

            $subtotal = Money::fromDecimal('42.25', 'USD');

            $context = app(CheckoutDiscountResolver::class)
                ->resolve($this->buildInput('save5'), $subtotal);

            $this->assertSame(550, $context->discountTotal->amountCents());
            $this->assertSame('5.50', $context->discountTotal->toDecimalString());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_discount_total_is_capped_at_subtotal_for_fixed_promotion(): void
    {
        $now = Carbon::parse('2026-07-04 12:00:00');
        Carbon::setTestNow($now);
        try {
            $promotion = Promotion::query()->create([
                'name' => 'Huge fixed',
                'code' => 'PROMOHUGE',
                'type' => PromotionType::FIXED->value,
                'value' => 100.00,
                'is_active' => true,
                'starts_at' => $now->copy()->subHour(),
                'ends_at' => $now->copy()->addHour(),
            ]);

            Coupon::query()->create([
                'promotion_id' => $promotion->id,
                'code' => 'HUGE',
                'is_active' => true,
            ]);

            $subtotal = Money::fromDecimal('20.00', 'USD');

            $context = app(CheckoutDiscountResolver::class)
                ->resolve($this->buildInput('huge'), $subtotal);

            $this->assertSame(2000, $context->discountTotal->amountCents());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_percent_promotion_rejects_value_above_one_hundred_defensively(): void
    {
        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessage('Promotion type is invalid.');

        $now = Carbon::parse('2026-07-04 12:00:00');
        Carbon::setTestNow($now);
        try {
            $promotion = Promotion::query()->create([
                'name' => 'Broken percent',
                'code' => 'PROMOBROKEN',
                'type' => PromotionType::PERCENT->value,
                // Inserted directly to simulate a domain call without the HTTP
                // validation guard; the resolver must defend its own boundary.
                'value' => 150,
                'is_active' => true,
                'starts_at' => $now->copy()->subHour(),
                'ends_at' => $now->copy()->addHour(),
            ]);

            Coupon::query()->create([
                'promotion_id' => $promotion->id,
                'code' => 'BROKEN',
                'is_active' => true,
            ]);

            app(CheckoutDiscountResolver::class)
                ->resolve($this->buildInput('broken'), Money::fromDecimal('10.00', 'USD'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_resolver_returns_zero_discount_when_no_coupon_code_provided(): void
    {
        $subtotal = Money::fromDecimal('100.00', 'USD');

        $context = app(CheckoutDiscountResolver::class)
            ->resolve($this->buildInput(null), $subtotal);

        $this->assertSame(0, $context->discountTotal->amountCents());
        $this->assertNull($context->coupon);
        $this->assertNull($context->promotion);
    }

    private function buildInput(?string $couponCode): CheckoutPlaceOrderInputDto
    {
        return CheckoutPlaceOrderInputDto::fromValidated([
            'guest_token' => 'resolver-test-token',
            'email' => 'resolver@example.com',
            'currency' => 'USD',
            'coupon_code' => $couponCode,
            'billing_address' => [
                'line1' => '1 Main St',
                'city' => 'Test',
                'country' => 'US',
                'postcode' => '00000',
            ],
            'shipping_address' => [
                'line1' => '1 Main St',
                'city' => 'Test',
                'country' => 'US',
                'postcode' => '00000',
            ],
        ]);
    }
}
