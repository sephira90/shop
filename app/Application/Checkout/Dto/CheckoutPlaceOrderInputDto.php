<?php

declare(strict_types=1);

namespace App\Application\Checkout\Dto;

use App\Support\Data\TypedValue;

final readonly class CheckoutPlaceOrderInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        /** @var array<string, mixed> $billingAddress */
        $billingAddress = is_array($validated['billing_address'] ?? null) ? $validated['billing_address'] : [];
        /** @var array<string, mixed> $shippingAddress */
        $shippingAddress = is_array($validated['shipping_address'] ?? null) ? $validated['shipping_address'] : [];

        return new self(
            guestToken: self::normalizeNullableString($validated['guest_token'] ?? null),
            email: TypedValue::trimmedString($validated['email']),
            currency: self::normalizeCurrency($validated['currency'] ?? null),
            couponCode: self::normalizeCouponCode($validated['coupon_code'] ?? null),
            billingAddress: CheckoutAddressInputDto::fromValidated($billingAddress),
            shippingAddress: CheckoutAddressInputDto::fromValidated($shippingAddress),
        );
    }

    public function __construct(
        public ?string $guestToken,
        public string $email,
        public string $currency,
        public ?string $couponCode,
        public CheckoutAddressInputDto $billingAddress,
        public CheckoutAddressInputDto $shippingAddress,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toHashPayload(): array
    {
        return [
            'guest_token' => $this->guestToken,
            'email' => $this->email,
            'currency' => $this->currency,
            'coupon_code' => $this->couponCode,
            'billing_address' => $this->billingAddress->toArray(),
            'shipping_address' => $this->shippingAddress->toArray(),
        ];
    }

    private static function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private static function normalizeCurrency(mixed $value): string
    {
        if (! is_string($value)) {
            return 'USD';
        }

        $normalized = strtoupper(trim($value));

        return $normalized !== '' ? $normalized : 'USD';
    }

    private static function normalizeCouponCode(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        return $normalized !== '' ? $normalized : null;
    }
}
