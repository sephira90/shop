<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthApplicationException;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Symfony\Component\HttpFoundation\Response;

final class VerifyAuthEmailHandler
{
    /**
     * Execute verify-email command.
     */
    public function handle(VerifyAuthEmailCommand $command): string
    {
        $user = User::query()->find($command->userId);
        if (! $user instanceof User) {
            throw new AuthApplicationException(
                'User not found.',
                Response::HTTP_NOT_FOUND,
            );
        }

        if (! hash_equals($command->hash, sha1($user->getEmailForVerification()))) {
            throw new AuthApplicationException(
                'Invalid verification hash.',
                Response::HTTP_FORBIDDEN,
            );
        }

        if ($user->hasVerifiedEmail()) {
            return 'Email already verified.';
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return 'Email verified successfully.';
    }
}
