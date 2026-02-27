<?php

declare(strict_types=1);

namespace App\Application\Auth\Support;

use App\Application\Auth\Dto\AuthUserDto;
use App\Models\User;

final class AuthUserDtoMapper
{
    /**
     * Build normalized auth user DTO.
     */
    public function map(User $user): AuthUserDto
    {
        return new AuthUserDto(
            id: $user->id,
            firstName: $user->first_name,
            lastName: $user->last_name,
            name: $user->name,
            email: $user->email,
            phone: $user->phone,
            isEmailVerified: $user->hasVerifiedEmail(),
            roles: $user->roles()->pluck('name')->all(),
        );
    }
}
