<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Contracts\AuthAuditLogger;
use App\Application\Auth\Contracts\AuthPasswordBrokerRepository;
use App\Application\Auth\Support\AuthAuditContextResolver;
use App\Application\Auth\Support\AuthAuditEvent;
use App\Support\Data\TypedValue;
use Symfony\Component\HttpFoundation\Response;

final class ForgotAuthPasswordHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthPasswordBrokerRepository $authPasswordBrokerRepository,
        private readonly AuthAuditLogger $authAuditLogger,
        private readonly AuthAuditContextResolver $authAuditContextResolver,
    ) {}

    /**
     * Execute forgot-password command.
     */
    public function handle(ForgotAuthPasswordCommand $command): string
    {
        $status = $this->authPasswordBrokerRepository->sendResetLink($command->input->email);

        if (! $this->authPasswordBrokerRepository->isResetLinkSentStatus($status)) {
            throw new AuthApplicationException(
                __($status),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $this->authAuditLogger->log(
            AuthAuditEvent::PasswordResetRequested,
            $this->authAuditContextResolver->resolveForEmail(
                userId: null,
                email: $command->input->email,
            ),
        );

        return TypedValue::string(__($status));
    }
}
