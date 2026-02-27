<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Dto\AuthTokenResultDto;
use App\Application\Auth\Support\AuthUserDtoMapper;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

final class LoginAuthUserHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly AuthUserDtoMapper $authUserDtoMapper,
    ) {}

    /**
     * Execute auth login command.
     */
    public function handle(LoginAuthUserCommand $command): AuthTokenResultDto
    {
        $input = $command->input;
        $user = User::query()->where('email', $input->email)->first();

        if (! $user instanceof User || ! Hash::check($input->password, $user->password)) {
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
            token: $user->createToken($deviceName)->plainTextToken,
            user: $this->authUserDtoMapper->map($user),
        );
    }
}
