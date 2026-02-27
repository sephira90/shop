<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthApplicationException;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class ResetAuthPasswordHandler
{
    /**
     * Execute reset-password command.
     */
    public function handle(ResetAuthPasswordCommand $command): string
    {
        $status = Password::reset(
            [
                'token' => $command->input->token,
                'email' => $command->input->email,
                'password' => $command->input->password,
                'password_confirmation' => $command->input->password,
            ],
            static function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new AuthApplicationException(
                __($status),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return __($status);
    }
}
