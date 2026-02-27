<?php

declare(strict_types=1);

namespace App\Application\Auth\Dto;

final readonly class RegisterAuthInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            firstName: trim((string) $validated['first_name']),
            lastName: trim((string) $validated['last_name']),
            email: trim((string) $validated['email']),
            phone: self::normalizeNullableString($validated['phone'] ?? null),
            password: (string) $validated['password'],
        );
    }

    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $phone,
        public string $password,
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
