<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Contracts\AuthAuditLogger;
use App\Application\Auth\Contracts\AuthPasswordBrokerRepository;
use App\Application\Auth\Contracts\AuthUserRepository;
use App\Application\Auth\Support\AuthAuditContext;
use App\Application\Auth\Support\AuthAuditContextResolver;
use App\Application\Auth\Support\AuthAuditEvent;
use App\Support\Data\TypedValue;
use Symfony\Component\HttpFoundation\Response;

final class ResetAuthPasswordHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthPasswordBrokerRepository $authPasswordBrokerRepository,
        private readonly AuthUserRepository $authUserRepository,
        private readonly AuthAuditLogger $authAuditLogger,
        private readonly AuthAuditContextResolver $authAuditContextResolver,
    ) {}

    /**
     * Execute reset-password command.
     */
    public function handle(ResetAuthPasswordCommand $command): string
    {
        $status = $this->authPasswordBrokerRepository->resetPassword($command->input);

        if (! $this->authPasswordBrokerRepository->isPasswordResetStatus($status)) {
            throw new AuthApplicationException(
                __($status),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user = $this->authUserRepository->findByEmail($command->input->email);
        $userId = $user === null ? null : (int) $user->id;

        $baseContext = $this->authAuditContextResolver->resolveForEmail(
            userId: $userId,
            email: $command->input->email,
        );

        $this->authAuditLogger->log(AuthAuditEvent::PasswordResetCompleted, $baseContext);
        $this->authAuditLogger->log(
            AuthAuditEvent::TokenRevoked,
            $this->withRevokeContext($baseContext, scope: 'all', reason: 'password_reset'),
        );

        return TypedValue::string(__($status));
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
