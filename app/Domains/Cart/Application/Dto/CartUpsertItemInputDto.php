<?php

declare(strict_types=1);

namespace App\Domains\Cart\Application\Dto;

use App\Support\Data\TypedValue;

final readonly class CartUpsertItemInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            productVariantId: TypedValue::int($validated['product_variant_id']),
            quantity: TypedValue::int($validated['quantity']),
            guestToken: self::normalizeGuestToken($validated['guest_token'] ?? null),
        );
    }

    public function __construct(
        public int $productVariantId,
        public int $quantity,
        public ?string $guestToken,
    ) {}

    private static function normalizeGuestToken(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $token = trim($value);

        return $token !== '' ? $token : null;
    }
}
