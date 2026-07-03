<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Auth\Contracts\AuthAuditLogger;
use App\Application\Auth\Contracts\AuthUserRepository;
use App\Application\Auth\Support\AuthAuditContext;
use App\Application\Auth\Support\AuthAuditContextResolver;
use App\Application\Auth\Support\AuthAuditEvent;
use App\Models\User;

final readonly class AuthActiveUserRevalidator
{
    public function __construct(
        private AuthUserRepository $authUserRepository,
        private AuthAuditLogger $authAuditLogger,
        private AuthAuditContextResolver $authAuditContextResolver,
    ) {}

    public function revalidate(User $user): bool
    {
        if ((bool) $user->is_active) {
            return true;
        }

        $this->authUserRepository->revokeAllAccessTokens($user);

        $this->authAuditLogger->log(
            AuthAuditEvent::TokenRevoked,
            $this->withRevokeContext(
                $this->authAuditContextResolver->resolveForUser((int) $user->id),
                scope: 'all',
                reason: 'inactive_user',
            ),
        );

        return false;
    }

    private function withRevokeContext(AuthAuditContext $base, string $scope, string $reason): AuthAuditContext
    {
        return new AuthAuditContext(
            userId: $base->userId,
            emailHash: $base->emailHash,
            clientIp: $base->clientIp,
            userAgent: $base->userAgent,
            correlationId: $base->correlationId,
            tokenScope: $scope,
            revokeReason: $reason,
        );
    }
}
