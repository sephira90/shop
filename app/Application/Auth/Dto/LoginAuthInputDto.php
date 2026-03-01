<?php

declare(strict_types=1);

namespace App\Application\Auth\Dto;

use App\Support\Data\TypedValue;

final readonly class LoginAuthInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            email: TypedValue::trimmedString($validated['email']),
            password: TypedValue::string($validated['password']),
            deviceName: self::normalizeNullableString($validated['device_name'] ?? null),
            guestToken: self::normalizeNullableString($validated['guest_token'] ?? null),
        );
    }

    public function __construct(
        public string $email,
        public string $password,
        public ?string $deviceName,
        public ?string $guestToken,
    ) {}

    /**
     * Normalize nullable string input.
     */
    private static function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
