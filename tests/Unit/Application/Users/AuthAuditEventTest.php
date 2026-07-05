<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Users;

use App\Domains\Users\Support\AuthAuditContext;
use App\Domains\Users\Support\AuthAuditEvent;
use PHPUnit\Framework\TestCase;

final class AuthAuditEventTest extends TestCase
{
    /**
     * Stable literal contract: renaming a value silently breaks observability consumers.
     */
    public function test_event_values_are_stable_literals(): void
    {
        self::assertSame('login.succeeded', AuthAuditEvent::LoginSucceeded->value);
        self::assertSame('login.failed', AuthAuditEvent::LoginFailed->value);
        self::assertSame('logout', AuthAuditEvent::Logout->value);
        self::assertSame('token.issued', AuthAuditEvent::TokenIssued->value);
        self::assertSame('token.revoked', AuthAuditEvent::TokenRevoked->value);
        self::assertSame('password.reset.requested', AuthAuditEvent::PasswordResetRequested->value);
        self::assertSame('password.reset.completed', AuthAuditEvent::PasswordResetCompleted->value);
        self::assertSame('email.verified', AuthAuditEvent::EmailVerified->value);
    }

    public function test_event_taxonomy_covers_required_security_surface(): void
    {
        $cases = array_map(static fn (AuthAuditEvent $event): string => $event->value, AuthAuditEvent::cases());

        self::assertContains('login.succeeded', $cases);
        self::assertContains('login.failed', $cases);
        self::assertContains('logout', $cases);
        self::assertContains('token.issued', $cases);
        self::assertContains('token.revoked', $cases);
        self::assertContains('password.reset.requested', $cases);
        self::assertContains('password.reset.completed', $cases);
        self::assertContains('email.verified', $cases);
    }

    public function test_context_to_log_array_omits_null_fields(): void
    {
        $context = new AuthAuditContext(
            userId: 42,
            emailHash: 'sha256-hash',
            clientIp: null,
            userAgent: null,
            correlationId: null,
            tokenScope: null,
            revokeReason: null,
        );

        self::assertSame(['user_id' => 42, 'email_hash' => 'sha256-hash'], $context->toLogArray());
    }

    public function test_context_with_full_revoke_payload_keeps_whitelisted_keys_only(): void
    {
        $context = new AuthAuditContext(
            userId: 7,
            emailHash: 'sha256-hash',
            clientIp: '1.2.3.4',
            userAgent: 'TestAgent',
            correlationId: 'uuid-123',
            tokenScope: 'all',
            revokeReason: 'inactive_user',
        );

        self::assertSame(
            [
                'user_id' => 7,
                'email_hash' => 'sha256-hash',
                'client_ip' => '1.2.3.4',
                'user_agent' => 'TestAgent',
                'correlation_id' => 'uuid-123',
                'token_scope' => 'all',
                'revoke_reason' => 'inactive_user',
            ],
            $context->toLogArray(),
        );
    }

    public function test_with_email_hash_lowercases_and_hashes_email(): void
    {
        $context = AuthAuditContext::withEmailHash(
            userId: null,
            email: 'John.Doe@Example.com ',
            clientIp: '1.2.3.4',
            userAgent: 'TestAgent',
            correlationId: 'uuid',
        );

        $normalizedHash = hash('sha256', 'john.doe@example.com');

        self::assertNull($context->userId);
        self::assertSame($normalizedHash, $context->emailHash);
        self::assertSame('1.2.3.4', $context->clientIp);
    }

    public function test_with_email_hash_yields_null_when_email_is_null(): void
    {
        $context = AuthAuditContext::withEmailHash(null, null);

        self::assertNull($context->emailHash);
        self::assertNull($context->userId);
    }

    /**
     * Whitelist leak test: the serialized payload must contain only the explicitly
     * whitelisted keys. Any new field silently expands what leaves the auth boundary.
     */
    public function test_to_log_array_never_exposes_non_whitelisted_keys(): void
    {
        $context = new AuthAuditContext(
            userId: 1,
            emailHash: 'h',
            clientIp: '1.1.1.1',
            userAgent: 'UA',
            correlationId: 'cid',
            tokenScope: 'current',
            revokeReason: 'logout',
        );

        $payload = $context->toLogArray();

        $allowed = [
            'user_id',
            'email_hash',
            'client_ip',
            'user_agent',
            'correlation_id',
            'token_scope',
            'revoke_reason',
        ];

        self::assertSame($allowed, array_keys($payload));
    }
}
