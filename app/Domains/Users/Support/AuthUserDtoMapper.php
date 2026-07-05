<?php

declare(strict_types=1);

namespace App\Domains\Users\Support;

use App\Domains\Users\Application\Dto\AuthUserDto;
use App\Models\User;
use App\Support\Data\TypedValue;

final class AuthUserDtoMapper
{
    /**
     * Build normalized auth user DTO.
     */
    public function map(User $user): AuthUserDto
    {
        return new AuthUserDto(
            id: $user->id,
            firstName: (string) $user->first_name,
            lastName: (string) $user->last_name,
            name: $user->name,
            email: $user->email,
            phone: $user->phone,
            isEmailVerified: $user->hasVerifiedEmail(),
            roles: TypedValue::stringList($user->roles()->pluck('name')->all()),
        );
    }
}
