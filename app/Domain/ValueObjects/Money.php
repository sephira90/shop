<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use DomainException;

final readonly class Money
{
    private const int FRACTION_SCALE = 2;

    private function __construct(
        private int $amountCents,
        private string $currency,
    ) {}

    public static function zero(string $currency): self
    {
        return self::fromCents(0, $currency);
    }

    public static function fromCents(int $amountCents, string $currency): self
    {
        return new self(
            amountCents: $amountCents,
            currency: self::normalizeCurrency($currency),
        );
    }

    public static function fromDecimal(float|int|string $amount, string $currency): self
    {
        $normalizedAmount = self::normalizeAmount($amount);

        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $normalizedAmount)) {
            throw new DomainException('Money amount format is invalid.');
        }

        $sign = str_starts_with($normalizedAmount, '-') ? -1 : 1;
        $unsignedAmount = ltrim($normalizedAmount, '-');
        $parts = explode('.', $unsignedAmount, 2);
        $major = (int) $parts[0];
        $minor = (int) str_pad($parts[1] ?? '0', self::FRACTION_SCALE, '0');
        $amountCents = ($major * 100 + $minor) * $sign;

        return self::fromCents($amountCents, $currency);
    }

    public function amountCents(): int
    {
        return $this->amountCents;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(
            amountCents: $this->amountCents + $other->amountCents,
            currency: $this->currency,
        );
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(
            amountCents: $this->amountCents - $other->amountCents,
            currency: $this->currency,
        );
    }

    public function multiply(int $factor): self
    {
        return new self(
            amountCents: $this->amountCents * $factor,
            currency: $this->currency,
        );
    }

    public function min(self $other): self
    {
        $this->assertSameCurrency($other);

        return $this->amountCents <= $other->amountCents ? $this : $other;
    }

    public function percentage(string $rate): self
    {
        if (preg_match('/^\d+(?:\.\d{1,4})?$/', $rate) !== 1) {
            throw new DomainException('Money percentage rate format is invalid.');
        }

        $ratePerMillion = $this->rateToPerMillion($rate);
        // Integer half-up rounding of amount_cents * rate / 100, where the
        // rate is expressed as per-million (rate * 10000) to preserve up to
        // four decimal places without an intermediate float.
        $scaledAmount = $this->amountCents * $ratePerMillion;
        $amountCents = intdiv($scaledAmount + 500000, 1000000);

        return new self(
            amountCents: $amountCents,
            currency: $this->currency,
        );
    }

    /**
     * Legacy float-rate alias retained for backward compatibility with callers
     * that do not have an exact decimal source. New code must pass an exact
     * decimal string to {@see percentage()} to avoid float-precision loss.
     */
    public function percentageFloat(float $rate): self
    {
        return $this->percentage(sprintf('%.4F', $rate));
    }

    /**
     * Convert a validated rate string into per-million units (rate * 10000).
     *
     * Accepts up to four decimal places (e.g. `12.995` -> 129950) so the
     * caller can keep the full precision without an intermediate float.
     */
    private function rateToPerMillion(string $rate): int
    {
        $parts = explode('.', $rate);
        $whole = (int) $parts[0];
        $fraction = $parts[1] ?? '';

        return $whole * 10000 + (int) substr(str_pad($fraction, 4, '0'), 0, 4);
    }

    public function toFloat(): float
    {
        return round($this->amountCents / 100, self::FRACTION_SCALE);
    }

    public function toDecimalString(): string
    {
        $absoluteCents = abs($this->amountCents);
        $major = intdiv($absoluteCents, 100);
        $minor = $absoluteCents % 100;

        return sprintf(
            '%s%d.%02d',
            $this->amountCents < 0 ? '-' : '',
            $major,
            $minor,
        );
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new DomainException('Money currency mismatch.');
        }
    }

    private static function normalizeCurrency(string $currency): string
    {
        $normalizedCurrency = strtoupper(trim($currency));

        if ($normalizedCurrency === '') {
            throw new DomainException('Money currency is required.');
        }

        return $normalizedCurrency;
    }

    private static function normalizeAmount(float|int|string $amount): string
    {
        if (is_string($amount)) {
            return trim($amount);
        }

        return number_format((float) $amount, self::FRACTION_SCALE, '.', '');
    }
}
