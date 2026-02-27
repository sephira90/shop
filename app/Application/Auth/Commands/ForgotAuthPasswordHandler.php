<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthApplicationException;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;

final class ForgotAuthPasswordHandler
{
    /**
     * Execute forgot-password command.
     */
    public function handle(ForgotAuthPasswordCommand $command): string
    {
        $status = Password::sendResetLink([
            'email' => $command->input->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new AuthApplicationException(
                __($status),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return __($status);
    }
}
