<?php

declare(strict_types=1);

namespace App\Domains\Users\Support;

/**
 * Auth audit context payload.
 *
 * Field set is the explicit whitelist of what may leave the auth boundary
 * into observability logs. Passwords, tokens, token ids, and raw emails on
 * failure paths are prohibited; identity on failure paths is the sha256
 * email hash only.
 */
final readonly class AuthAuditContext
{
    public function __construct(
        public ?int $userId = null,
        public ?string $emailHash = null,
        public ?string $clientIp = null,
        public ?string $userAgent = null,
        public ?string $correlationId = null,
        public ?string $tokenScope = null,
        public ?string $revokeReason = null,
    ) {}

    /**
     * Build a context with the email replaced by its sha256 hash.
     */
    public static function withEmailHash(
        ?int $userId,
        ?string $email,
        ?string $clientIp = null,
        ?string $userAgent = null,
        ?string $correlationId = null,
    ): self {
        $normalizedEmail = $email === null ? null : trim(strtolower($email));

        return new self(
            userId: $userId,
            emailHash: $normalizedEmail === null ? null : hash('sha256', $normalizedEmail),
            clientIp: $clientIp,
            userAgent: $userAgent,
            correlationId: $correlationId,
        );
    }

    /**
     * Return a log-safe associative array, omitting null fields.
     *
     * @return array<string, string|int>
     */
    public function toLogArray(): array
    {
        $payload = [
            'user_id' => $this->userId,
            'email_hash' => $this->emailHash,
            'client_ip' => $this->clientIp,
            'user_agent' => $this->userAgent,
            'correlation_id' => $this->correlationId,
            'token_scope' => $this->tokenScope,
            'revoke_reason' => $this->revokeReason,
        ];

        $filtered = [];
        foreach ($payload as $key => $value) {
            if ($value !== null) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
