<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Auth;

use App\Application\Auth\Contracts\AuthAuditLogger;
use App\Application\Auth\Support\AuthAuditContext;
use App\Application\Auth\Support\AuthAuditEvent;
use Illuminate\Support\Facades\Log;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

final class ObservabilityAuthAuditLoggerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_log_writes_event_to_observability_channel_with_safe_context(): void
    {
        config()->set('observability.channel', 'observability');

        Log::shouldReceive('channel')->once()->with('observability')->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('auth.audit.login.succeeded', \Mockery::on(static function (array $payload): bool {
                return $payload === ['user_id' => 42, 'email_hash' => 'sha256-hash'];
            }));

        $logger = $this->app->make(AuthAuditLogger::class);
        $logger->log(
            AuthAuditEvent::LoginSucceeded,
            new AuthAuditContext(userId: 42, emailHash: 'sha256-hash'),
        );
    }

    public function test_log_uses_configured_channel_when_set(): void
    {
        config()->set('observability.channel', 'custom-audit');

        Log::shouldReceive('channel')->once()->with('custom-audit')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $logger = $this->app->make(AuthAuditLogger::class);
        $logger->log(AuthAuditEvent::Logout, new AuthAuditContext(userId: 1));
    }

    public function test_log_falls_back_to_default_channel_when_emission_fails_without_pii(): void
    {
        config()->set('observability.channel', 'observability');

        Log::shouldReceive('channel')->andThrow(new \RuntimeException('observability backend unavailable'));
        Log::shouldReceive('error')
            ->once()
            ->with('auth.audit_emission_failed', \Mockery::on(static function (array $payload): bool {
                return $payload['event'] === 'login.failed'
                    && $payload['reason'] === 'RuntimeException'
                    && ! array_key_exists('email_hash', $payload)
                    && ! array_key_exists('user_id', $payload);
            }));

        $logger = $this->app->make(AuthAuditLogger::class);
        $logger->log(
            AuthAuditEvent::LoginFailed,
            new AuthAuditContext(userId: 42, emailHash: 'sha256-hash'),
        );
    }
}
