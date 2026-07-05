<?php

declare(strict_types=1);

namespace App\Domains\Users\Application;

use App\Domains\Users\Contracts\AuthAuditLogger;
use App\Domains\Users\Contracts\AuthUserRepository;
use App\Domains\Users\Support\AuthAuditContextResolver;
use App\Domains\Users\Support\AuthAuditEvent;
use App\Models\User;
use App\Support\Data\TypedValue;
use Carbon\CarbonImmutable;
use UnexpectedValueException;

final readonly class AuthAccessTokenIssuer
{
    public function __construct(
        private AuthUserRepository $authUserRepository,
        private AuthAuditLogger $authAuditLogger,
        private AuthAuditContextResolver $authAuditContextResolver,
    ) {}

    public function issue(User $user, string $deviceName): string
    {
        $expirationMinutes = TypedValue::int(config('sanctum.expiration'));

        if ($expirationMinutes < 1) {
            throw new UnexpectedValueException('Sanctum token expiration must be a positive integer.');
        }

        $token = $this->authUserRepository->issueAccessToken(
            $user,
            $deviceName,
            CarbonImmutable::now()->addMinutes($expirationMinutes),
        );

        $this->authAuditLogger->log(
            AuthAuditEvent::TokenIssued,
            $this->authAuditContextResolver->resolveForUser((int) $user->id),
        );

        return $token;
    }
}
