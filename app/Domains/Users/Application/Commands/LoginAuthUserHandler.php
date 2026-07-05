<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Commands;

use App\Contracts\CartServiceInterface;
use App\Domains\Users\Application\AuthAccessTokenIssuer;
use App\Domains\Users\Application\AuthApplicationException;
use App\Domains\Users\Application\Dto\AuthTokenResultDto;
use App\Domains\Users\Contracts\AuthAuditLogger;
use App\Domains\Users\Contracts\AuthUserRepository;
use App\Domains\Users\Support\AuthAuditContextResolver;
use App\Domains\Users\Support\AuthAuditEvent;
use App\Domains\Users\Support\AuthUserDtoMapper;
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
