<?php

declare(strict_types=1);

namespace App\Application\Auth\Support;

use App\Models\User;

final class AuthUserPayloadBuilder
{
    /**
     * Build normalized auth user payload.
     *
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_email_verified' => $user->hasVerifiedEmail(),
            'roles' => $user->roles()->pluck('name')->all(),
        ];
    }
}
