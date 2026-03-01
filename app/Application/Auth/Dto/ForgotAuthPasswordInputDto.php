<?php

declare(strict_types=1);

namespace App\Application\Auth\Dto;

use App\Support\Data\TypedValue;

final readonly class ForgotAuthPasswordInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            email: TypedValue::trimmedString($validated['email'] ?? ''),
        );
    }

    public function __construct(
        public string $email,
    ) {}
}
