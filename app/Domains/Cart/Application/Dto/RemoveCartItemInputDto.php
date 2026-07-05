<?php

declare(strict_types=1);

namespace App\Application\Cart\Dto;

final readonly class RemoveCartItemInputDto
{
    public static function fromRaw(mixed $guestToken, int $variantId): self
    {
        return new self(
            variantId: $variantId,
            guestToken: self::normalizeGuestToken($guestToken),
        );
    }

    public function __construct(
        public int $variantId,
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
