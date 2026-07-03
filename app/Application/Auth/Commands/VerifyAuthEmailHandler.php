<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Contracts\AuthAuditLogger;
use App\Application\Auth\Contracts\AuthUserRepository;
use App\Application\Auth\Support\AuthAuditContextResolver;
use App\Application\Auth\Support\AuthAuditEvent;
use Illuminate\Auth\Events\Verified;
use Symfony\Component\HttpFoundation\Response;

final class VerifyAuthEmailHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthUserRepository $authUserRepository,
        private readonly AuthAuditLogger $authAuditLogger,
        private readonly AuthAuditContextResolver $authAuditContextResolver,
    ) {}

    /**
     * Execute verify-email command.
     */
    public function handle(VerifyAuthEmailCommand $command): string
    {
        $user = $this->authUserRepository->findById($command->userId);
        if ($user === null) {
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

        if ($this->authUserRepository->markEmailAsVerified($user)) {
            event(new Verified($user));

            $this->authAuditLogger->log(
                AuthAuditEvent::EmailVerified,
                $this->authAuditContextResolver->resolveForEmail(
                    userId: (int) $user->id,
                    email: $user->getEmailForVerification(),
                ),
            );
        }

        return 'Email verified successfully.';
    }
}
