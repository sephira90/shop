<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Auth\Contracts\AuthUserRepository;
use App\Models\User;

final readonly class AuthActiveUserRevalidator
{
    public function __construct(
        private AuthUserRepository $authUserRepository,
    ) {}

    public function revalidate(User $user): bool
    {
        if ((bool) $user->is_active) {
            return true;
        }

        $this->authUserRepository->revokeAllAccessTokens($user);

        return false;
    }
}
