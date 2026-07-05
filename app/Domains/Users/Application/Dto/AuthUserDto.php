<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Dto;

final readonly class AuthUserDto
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $name,
        public string $email,
        public ?string $phone,
        public bool $isEmailVerified,
        public array $roles,
    ) {}

    /**
     * Convert DTO to transport payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_email_verified' => $this->isEmailVerified,
            'roles' => $this->roles,
        ];
    }
}
