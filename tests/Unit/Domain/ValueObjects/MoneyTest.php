<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\ValueObjects\Money;
use DomainException;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_from_decimal_normalizes_currency_and_scale(): void
    {
        $money = Money::fromDecimal('10.5', ' usd ');

        $this->assertSame(1050, $money->amountCents());
        $this->assertSame('USD', $money->currency());
        $this->assertSame(10.5, $money->toFloat());
        $this->assertSame('10.50', $money->toDecimalString());
    }

    public function test_add_subtract_and_min_operations_are_currency_safe(): void
    {
        $subtotal = Money::fromDecimal(100, 'USD');
        $discount = Money::fromDecimal(10.25, 'USD');
        $shipping = Money::fromDecimal(5.75, 'USD');
        $total = $subtotal->subtract($discount)->add($shipping);

        $this->assertSame(9550, $total->amountCents());
        $this->assertSame(95.5, $total->toFloat());
        $this->assertSame(575, $discount->min($shipping)->amountCents());
    }

    public function test_multiply_scales_amount_in_cents_without_float_rounding_drift(): void
    {
        $unitPrice = Money::fromDecimal('19.99', 'USD');
        $lineTotal = $unitPrice->multiply(3);

        $this->assertSame(5997, $lineTotal->amountCents());
        $this->assertSame('59.97', $lineTotal->toDecimalString());
    }

    public function test_percentage_uses_half_up_rounding(): void
    {
        $subtotal = Money::fromDecimal(99.99, 'USD');
        $tenPercent = $subtotal->percentage('10');

        $this->assertSame(1000, $tenPercent->amountCents());
        $this->assertSame(10.0, $tenPercent->toFloat());
    }

    public function test_operations_with_different_currency_throw_domain_exception(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Money currency mismatch.');

        Money::fromDecimal(10, 'USD')->add(Money::fromDecimal(5, 'EUR'));
    }
}
