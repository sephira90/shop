<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Dto;

final readonly class AuthTokenResultDto
{
    public function __construct(
        public string $token,
        public AuthUserDto $user,
    ) {}

    /**
     * Convert DTO to transport payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'user' => $this->user->toArray(),
        ];
    }
}
