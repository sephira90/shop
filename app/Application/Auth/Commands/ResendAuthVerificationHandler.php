<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

final class ResendAuthVerificationHandler
{
    /**
     * Execute resend-verification command.
     */
    public function handle(ResendAuthVerificationCommand $command): string
    {
        if ($command->user->hasVerifiedEmail()) {
            return 'Email already verified.';
        }

        $command->user->sendEmailVerificationNotification();

        return 'Verification email has been sent.';
    }
}
