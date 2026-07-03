<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Auth;

use App\Application\Auth\Support\AuthLoginRateLimitKey;
use PHPUnit\Framework\TestCase;

final class AuthLoginRateLimitKeyTest extends TestCase
{
    public function test_resolve_normalizes_email_and_includes_client_ip(): void
    {
        $resolver = new AuthLoginRateLimitKey;

        $normalized = $resolver->resolve(' User@Example.COM ', '203.0.113.10');
        $sameIdentity = $resolver->resolve('user@example.com', '203.0.113.10');
        $differentIp = $resolver->resolve('user@example.com', '203.0.113.11');

        $this->assertSame($normalized, $sameIdentity);
        $this->assertNotSame($normalized, $differentIp);
        $this->assertStringStartsWith(hash('sha256', 'user@example.com').'|', $normalized);
        $this->assertStringEndsWith('|203.0.113.10', $normalized);
        $this->assertStringNotContainsString('user@example.com', $normalized);
    }
}
