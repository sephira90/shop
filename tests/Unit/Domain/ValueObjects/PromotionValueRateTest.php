<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\ValueObjects\Money;
use DomainException;
use Tests\TestCase;

/**
 * Verifies Money::percentage() accepts an exact-decimal string rate and
 * never converts the rate to float before domain arithmetic.
 */
class PromotionValueRateTest extends TestCase
{
    public function test_percentage_accepts_decimal_string_rate_without_float_conversion(): void
    {
        $subtotal = Money::fromDecimal('99.99', 'USD');

        $discount = $subtotal->percentage('12.5');

        $this->assertSame(1250, $discount->amountCents());
        $this->assertSame('12.50', $discount->toDecimalString());
    }

    public function test_percentage_handles_four_decimal_places_with_half_up_rounding(): void
    {
        $subtotal = Money::fromDecimal('100.00', 'USD');

        // 12.995% of 100.00 = 12.995 → rounds half-up to 12.50 cents? No: 10000 * 129950 / 1000000 = 1299.5 → 1300 cents.
        $discount = $subtotal->percentage('12.995');

        $this->assertSame(1300, $discount->amountCents());
        $this->assertSame('13.00', $discount->toDecimalString());
    }

    public function test_percentage_half_up_at_cent_edge_for_integer_rate(): void
    {
        // 99.99 * 10% = 9.999 → rounds to 10.00 (half-up).
        $subtotal = Money::fromDecimal('99.99', 'USD');

        $discount = $subtotal->percentage('10');

        $this->assertSame(1000, $discount->amountCents());
    }

    public function test_percentage_half_up_at_fractional_cent(): void
    {
        // 1.00 * 0.01% = 0.0001 → rounds to 0.00.
        $subtotal = Money::fromDecimal('1.00', 'USD');

        $discount = $subtotal->percentage('0.01');

        $this->assertSame(0, $discount->amountCents());
    }

    public function test_percentage_half_up_rounds_up_at_exact_half_cent(): void
    {
        // 0.05 * 10% = 0.005 → rounds half-up to 0.01.
        $subtotal = Money::fromDecimal('0.05', 'USD');

        $discount = $subtotal->percentage('10');

        $this->assertSame(1, $discount->amountCents());
    }

    public function test_percentage_full_rate_returns_subtotal(): void
    {
        $subtotal = Money::fromDecimal('42.50', 'USD');

        $discount = $subtotal->percentage('100');

        $this->assertSame(4250, $discount->amountCents());
    }

    public function test_percentage_zero_rate_returns_zero(): void
    {
        $subtotal = Money::fromDecimal('42.50', 'USD');

        $discount = $subtotal->percentage('0');

        $this->assertSame(0, $discount->amountCents());
    }

    public function test_percentage_rejects_rate_with_more_than_four_decimal_places(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Money percentage rate format is invalid.');

        Money::fromDecimal('10.00', 'USD')->percentage('12.12345');
    }

    public function test_percentage_rejects_negative_rate(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Money percentage rate format is invalid.');

        Money::fromDecimal('10.00', 'USD')->percentage('-5');
    }

    public function test_percentage_rejects_non_numeric_rate(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Money percentage rate format is invalid.');

        Money::fromDecimal('10.00', 'USD')->percentage('abc');
    }

    public function test_percentage_rejects_empty_rate(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Money percentage rate format is invalid.');

        Money::fromDecimal('10.00', 'USD')->percentage('');
    }

    public function test_percentage_float_legacy_alias_preserves_existing_behavior(): void
    {
        $subtotal = Money::fromDecimal('99.99', 'USD');

        $discount = $subtotal->percentageFloat(10.0);

        $this->assertSame(1000, $discount->amountCents());
    }
}
