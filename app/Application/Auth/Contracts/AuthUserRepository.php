<?php

declare(strict_types=1);

namespace App\Application\Auth\Contracts;

use App\Application\Auth\Dto\RegisterAuthInputDto;
use App\Application\Auth\Dto\UpdateAuthProfileInputDto;
use App\Enums\RoleName;
use App\Models\User;
use DateTimeInterface;

interface AuthUserRepository
{
    public function createUser(RegisterAuthInputDto $input): User;

    public function findByEmail(string $email): ?User;

    public function findById(int $userId): ?User;

    public function isPasswordValid(?User $user, string $plainPassword): bool;

    public function issueAccessToken(User $user, string $deviceName, DateTimeInterface $expiresAt): string;

    public function revokeCurrentAccessToken(User $user): void;

    public function revokeAllAccessTokens(User $user): void;

    public function assignRole(User $user, RoleName|string $role): void;

    public function sendEmailVerification(User $user): void;

    public function markEmailAsVerified(User $user): bool;

    public function updateProfile(User $user, UpdateAuthProfileInputDto $input): User;

    public function updatePassword(User $user, string $password): void;
}
