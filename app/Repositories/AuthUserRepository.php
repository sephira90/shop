<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Application\Auth\Contracts\AuthUserRepository as AuthUserRepositoryContract;
use App\Application\Auth\Dto\RegisterAuthInputDto;
use App\Application\Auth\Dto\UpdateAuthProfileInputDto;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AuthUserRepository implements AuthUserRepositoryContract
{
    public function createUser(RegisterAuthInputDto $input): User
    {
        return User::query()->create([
            'first_name' => $input->firstName,
            'last_name' => $input->lastName,
            'name' => trim($input->firstName.' '.$input->lastName),
            'email' => $input->email,
            'phone' => $input->phone,
            'password' => $input->password,
        ]);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function findById(int $userId): ?User
    {
        return User::query()->find($userId);
    }

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return Hash::check($plainPassword, $user->password);
    }

    public function issueAccessToken(User $user, string $deviceName): string
    {
        return $user->createToken($deviceName)->plainTextToken;
    }

    public function revokeCurrentAccessToken(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function assignRole(User $user, RoleName|string $role): void
    {
        $user->assignRole($role);
    }

    public function sendEmailVerification(User $user): void
    {
        $user->sendEmailVerificationNotification();
    }

    public function markEmailAsVerified(User $user): bool
    {
        return $user->markEmailAsVerified();
    }

    public function updateProfile(User $user, UpdateAuthProfileInputDto $input): User
    {
        $user->update([
            'first_name' => $input->firstName,
            'last_name' => $input->lastName,
            'name' => trim($input->firstName.' '.$input->lastName),
            'phone' => $input->phone,
        ]);

        $fresh = $user->fresh();

        return $fresh instanceof User ? $fresh : $user;
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();
    }
}
