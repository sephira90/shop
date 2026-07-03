<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthAccessTokenIssuer;
use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Contracts\AuthAuditLogger;
use App\Application\Auth\Contracts\AuthUserRepository;
use App\Application\Auth\Dto\AuthTokenResultDto;
use App\Application\Auth\Support\AuthAuditContextResolver;
use App\Application\Auth\Support\AuthAuditEvent;
use App\Application\Auth\Support\AuthUserDtoMapper;
use App\Contracts\CartServiceInterface;
use Symfony\Component\HttpFoundation\Response;

final class LoginAuthUserHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthUserRepository $authUserRepository,
        private readonly AuthAccessTokenIssuer $authAccessTokenIssuer,
        private readonly CartServiceInterface $cartService,
        private readonly AuthUserDtoMapper $authUserDtoMapper,
        private readonly AuthAuditLogger $authAuditLogger,
        private readonly AuthAuditContextResolver $authAuditContextResolver,
    ) {}

    /**
     * Execute auth login command.
     */
    public function handle(LoginAuthUserCommand $command): AuthTokenResultDto
    {
        $input = $command->input;
        $user = $this->authUserRepository->findByEmail($input->email);
        $passwordIsValid = $this->authUserRepository->isPasswordValid($user, $input->password);

        // Active status is validated together with credentials to avoid leaking account state.
        if (
            ! $passwordIsValid
            || $user === null
            || ! (bool) $user->is_active
        ) {
            $this->authAuditLogger->log(
                AuthAuditEvent::LoginFailed,
                $this->authAuditContextResolver->resolveForEmail(
                    userId: $user?->id,
                    email: $input->email,
                ),
            );

            throw new AuthApplicationException(
                'Invalid credentials.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $this->authAuditLogger->log(
            AuthAuditEvent::LoginSucceeded,
            $this->authAuditContextResolver->resolveForEmail(
                userId: $user->id,
                email: $input->email,
            ),
        );

        if ($input->guestToken !== null) {
            $this->cartService->mergeGuestCart($user, $input->guestToken);
        }

        $deviceName = $input->deviceName ?? 'api-device';

        return new AuthTokenResultDto(
            token: $this->authAccessTokenIssuer->issue($user, $deviceName),
            user: $this->authUserDtoMapper->map($user),
        );
    }
}
