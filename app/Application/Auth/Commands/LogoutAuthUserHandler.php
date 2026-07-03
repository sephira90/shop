<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\Contracts\AuthAuditLogger;
use App\Application\Auth\Contracts\AuthUserRepository;
use App\Application\Auth\Support\AuthAuditContext;
use App\Application\Auth\Support\AuthAuditContextResolver;
use App\Application\Auth\Support\AuthAuditEvent;

final class LogoutAuthUserHandler
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
     * Execute auth logout command.
     */
    public function handle(LogoutAuthUserCommand $command): void
    {
        $this->authUserRepository->revokeCurrentAccessToken($command->user);

        $this->authAuditLogger->log(
            AuthAuditEvent::Logout,
            $this->authAuditContextResolver->resolveForUser((int) $command->user->id),
        );
        $this->authAuditLogger->log(
            AuthAuditEvent::TokenRevoked,
            $this->withRevokeContext(
                $this->authAuditContextResolver->resolveForUser((int) $command->user->id),
                scope: 'current',
                reason: 'logout',
            ),
        );
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
