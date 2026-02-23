<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Support\AuthUserPayloadBuilder;
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
        private readonly AuthUserPayloadBuilder $authUserPayloadBuilder,
    ) {}

    /**
     * Execute auth login command.
     *
     * @return array{token:string,user:array<string,mixed>}
     */
    public function handle(LoginAuthUserCommand $command): array
    {
        $payload = $command->payload;
        $user = User::query()->where('email', $payload['email'])->first();

        if (! $user instanceof User || ! Hash::check($payload['password'], $user->password)) {
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

        if (! empty($payload['guest_token'])) {
            $this->cartService->mergeGuestCart($user, (string) $payload['guest_token']);
        }

        return [
            'token' => $user->createToken((string) ($payload['device_name'] ?? 'api-device'))->plainTextToken,
            'user' => $this->authUserPayloadBuilder->build($user),
        ];
    }
}
