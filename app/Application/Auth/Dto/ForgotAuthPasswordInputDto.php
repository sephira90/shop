<?php

declare(strict_types=1);

namespace App\Application\Auth\Dto;

final readonly class ForgotAuthPasswordInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            email: trim((string) ($validated['email'] ?? '')),
        );
    }

    public function __construct(
        public string $email,
    ) {}
}
