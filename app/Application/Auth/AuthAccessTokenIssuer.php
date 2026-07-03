<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Auth\Contracts\AuthUserRepository;
use App\Models\User;
use App\Support\Data\TypedValue;
use Carbon\CarbonImmutable;
use UnexpectedValueException;

final readonly class AuthAccessTokenIssuer
{
    public function __construct(
        private AuthUserRepository $authUserRepository,
    ) {}

    public function issue(User $user, string $deviceName): string
    {
        $expirationMinutes = TypedValue::int(config('sanctum.expiration'));

        if ($expirationMinutes < 1) {
            throw new UnexpectedValueException('Sanctum token expiration must be a positive integer.');
        }

        return $this->authUserRepository->issueAccessToken(
            $user,
            $deviceName,
            CarbonImmutable::now()->addMinutes($expirationMinutes),
        );
    }
}
