<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Dto;

use App\Support\Data\TypedValue;

final readonly class ResetAuthPasswordInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            token: TypedValue::trimmedString($validated['token'] ?? ''),
            email: TypedValue::trimmedString($validated['email'] ?? ''),
            password: TypedValue::string($validated['password'] ?? ''),
        );
    }

    public function __construct(
        public string $token,
        public string $email,
        public string $password,
    ) {}
}
