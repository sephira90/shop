<?php

declare(strict_types=1);

namespace App\Domains\Users\Repositories;

use App\Domains\Users\Application\Dto\ResetAuthPasswordInputDto;
use App\Domains\Users\Contracts\AuthPasswordBrokerRepository as AuthPasswordBrokerRepositoryContract;
use App\Domains\Users\Contracts\AuthUserRepository as AuthUserRepositoryContract;
use App\Models\User;
use App\Support\Data\TypedValue;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
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
        $status = Password::reset(
            [
                'token' => $input->token,
                'email' => $input->email,
                'password' => $input->password,
                'password_confirmation' => $input->password,
            ],
            function (User $user, string $password): void {
                DB::transaction(function () use ($user, $password): void {
                    $this->authUserRepository->updatePassword($user, $password);
                    $this->authUserRepository->revokeAllAccessTokens($user);
                });

                event(new PasswordReset($user));
            },
        );

        return TypedValue::string($status);
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
