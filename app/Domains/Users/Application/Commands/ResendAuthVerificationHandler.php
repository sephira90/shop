<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Commands;

use App\Domains\Users\Contracts\AuthUserRepository;

final class ResendAuthVerificationHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthUserRepository $authUserRepository,
    ) {}

    /**
     * Execute resend-verification command.
     */
    public function handle(ResendAuthVerificationCommand $command): string
    {
        if ($command->user->hasVerifiedEmail()) {
            return 'Email already verified.';
        }

        $this->authUserRepository->sendEmailVerification($command->user);

        return 'Verification email has been sent.';
    }
}
