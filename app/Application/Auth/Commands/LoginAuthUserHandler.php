<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Contracts\AuthUserRepository;
use App\Application\Auth\Dto\AuthTokenResultDto;
use App\Application\Auth\Support\AuthUserDtoMapper;
use App\Services\Cart\CartService;
use Symfony\Component\HttpFoundation\Response;

final class LoginAuthUserHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthUserRepository $authUserRepository,
        private readonly CartService $cartService,
        private readonly AuthUserDtoMapper $authUserDtoMapper,
    ) {}

    /**
     * Execute auth login command.
     */
    public function handle(LoginAuthUserCommand $command): AuthTokenResultDto
    {
        $input = $command->input;
        $user = $this->authUserRepository->findByEmail($input->email);

        if ($user === null || ! $this->authUserRepository->isPasswordValid($user, $input->password)) {
            throw new AuthApplicationException(
                'Invalid credentials.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (! $user->is_active) {
            throw new AuthApplicationException(
                'User account is disabled.',
                Response::HTTP_FORBIDDEN,
            );
        }

        if ($input->guestToken !== null) {
            $this->cartService->mergeGuestCart($user, $input->guestToken);
        }

        $deviceName = $input->deviceName ?? 'api-device';

        return new AuthTokenResultDto(
            token: $this->authUserRepository->issueAccessToken($user, $deviceName),
            user: $this->authUserDtoMapper->map($user),
        );
    }
}
