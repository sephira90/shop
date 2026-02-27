<?php

declare(strict_types=1);

namespace App\Application\Auth\Dto;

final readonly class ResetAuthPasswordInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            token: trim((string) ($validated['token'] ?? '')),
            email: trim((string) ($validated['email'] ?? '')),
            password: (string) ($validated['password'] ?? ''),
        );
    }

    public function __construct(
        public string $token,
        public string $email,
        public string $password,
    ) {}
}
