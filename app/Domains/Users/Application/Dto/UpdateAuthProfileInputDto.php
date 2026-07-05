<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Dto;

use App\Support\Data\TypedValue;

final readonly class UpdateAuthProfileInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            firstName: TypedValue::trimmedString($validated['first_name']),
            lastName: TypedValue::trimmedString($validated['last_name']),
            phone: self::normalizeNullableString($validated['phone'] ?? null),
        );
    }

    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $phone,
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
