<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Application\Auth\Contracts\AuthPasswordBrokerRepository as AuthPasswordBrokerRepositoryContract;
use App\Application\Auth\Contracts\AuthUserRepository as AuthUserRepositoryContract;
use App\Application\Auth\Dto\ResetAuthPasswordInputDto;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;

final readonly class AuthPasswordBrokerRepository implements AuthPasswordBrokerRepositoryContract
{
    public function __construct(
        private AuthUserRepositoryContract $authUserRepository,
    ) {}

    public function sendResetLink(string $email): string
    {
        return Password::sendResetLink([
            'email' => $email,
        ]);
    }

    public function resetPassword(ResetAuthPasswordInputDto $input): string
    {
        return Password::reset(
            [
                'token' => $input->token,
                'email' => $input->email,
                'password' => $input->password,
                'password_confirmation' => $input->password,
            ],
            function (User $user, string $password): void {
                $this->authUserRepository->updatePassword($user, $password);

                event(new PasswordReset($user));
            },
        );
    }

    public function isResetLinkSentStatus(string $status): bool
    {
        return $status === Password::RESET_LINK_SENT;
    }

    public function isPasswordResetStatus(string $status): bool
    {
        return $status === Password::PASSWORD_RESET;
    }
}
