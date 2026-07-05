<?php

declare(strict_types=1);

namespace Tests\Support\Auth;

use App\Domains\Users\Application\Dto\RegisterAuthInputDto;
use App\Domains\Users\Application\Dto\UpdateAuthProfileInputDto;
use App\Domains\Users\Contracts\AuthUserRepository;
use App\Enums\RoleName;
use App\Models\User;
use DateTimeInterface;

/**
 * Test fake for AuthUserRepository used by handler unit tests where direct
 * control of findByEmail/isPasswordValid is required without facade mocking.
 */
final class AuthUserRepositoryFake implements AuthUserRepository
{
    private int $passwordVerificationCount = 0;

    public function __construct(
        private ?User $findByEmailResult = null,
        private ?bool $passwordValidResult = null,
    ) {}

    public function createUser(RegisterAuthInputDto $input): User
    {
        throw new \RuntimeException('Not supported in this fake.');
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findByEmailResult;
    }

    public function findById(int $userId): ?User
    {
        throw new \RuntimeException('Not supported in this fake.');
    }

    public function isPasswordValid(?User $user, string $plainPassword): bool
    {
        $this->passwordVerificationCount++;

        return $this->passwordValidResult ?? false;
    }

    public function issueAccessToken(User $user, string $deviceName, DateTimeInterface $expiresAt): string
    {
        throw new \RuntimeException('Not supported in this fake.');
    }

    public function revokeCurrentAccessToken(User $user): void
    {
        throw new \RuntimeException('Not supported in this fake.');
    }

    public function revokeAllAccessTokens(User $user): void
    {
        throw new \RuntimeException('Not supported in this fake.');
    }

    public function assignRole(User $user, RoleName|string $role): void
    {
        throw new \RuntimeException('Not supported in this fake.');
    }

    public function sendEmailVerification(User $user): void
    {
        throw new \RuntimeException('Not supported in this fake.');
    }

    public function markEmailAsVerified(User $user): bool
    {
        throw new \RuntimeException('Not supported in this fake.');
    }

    public function updateProfile(User $user, UpdateAuthProfileInputDto $input): User
    {
        throw new \RuntimeException('Not supported in this fake.');
    }

    public function updatePassword(User $user, string $password): void
    {
        throw new \RuntimeException('Not supported in this fake.');
    }

    public function passwordVerificationCount(): int
    {
        return $this->passwordVerificationCount;
    }
}
