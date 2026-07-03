<?php

declare(strict_types=1);

namespace App\Application\Auth\Support;

use Illuminate\Http\Request;

/**
 * Resolve auth audit context from the current HTTP request.
 *
 * Single point where transport metadata (client IP, user-agent, correlation
 * id) is captured for audit emission, keeping handlers free of direct request
 * coupling. Non-HTTP contexts resolve a context with null transport fields.
 */
final readonly class AuthAuditContextResolver
{
    private const string CORRELATION_HEADER = 'X-Correlation-Id';

    public function resolveForEmail(?int $userId, ?string $email): AuthAuditContext
    {
        return AuthAuditContext::withEmailHash(
            userId: $userId,
            email: $email,
            clientIp: $this->clientIp(),
            userAgent: $this->userAgent(),
            correlationId: $this->correlationId(),
        );
    }

    public function resolveForUser(int $userId): AuthAuditContext
    {
        return new AuthAuditContext(
            userId: $userId,
            clientIp: $this->clientIp(),
            userAgent: $this->userAgent(),
            correlationId: $this->correlationId(),
        );
    }

    private function clientIp(): ?string
    {
        $ip = $this->currentRequest()->ip();

        return $ip === null ? null : $ip;
    }

    private function userAgent(): ?string
    {
        $userAgent = $this->currentRequest()->userAgent();

        return $userAgent === null || $userAgent === '' ? null : $userAgent;
    }

    private function correlationId(): ?string
    {
        $value = $this->currentRequest()->headers->get(self::CORRELATION_HEADER);

        return $value === null || $value === '' ? null : $value;
    }

    private function currentRequest(): Request
    {
        return app(Request::class);
    }
}
