<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Users\Contracts\AuthAuditLogger;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Support\Auth\AuthAuditLoggerSpy;
use Tests\TestCase;

/**
 * F3-79 feature coverage: each credential-sensitive auth flow emits exactly
 * its expected structured audit record through the AuthAuditLogger contract.
 *
 * The logger is swapped for an in-memory spy so the assertions observe the
 * application boundary directly without coupling to the log facade plumbing
 * shared with telemetry and the exception handler.
 */
class AuthAuditTrailFeatureTest extends TestCase
{
    use RefreshDatabase;

    private AuthAuditLoggerSpy $auditSpy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->auditSpy = new AuthAuditLoggerSpy;
        $this->app->instance(AuthAuditLogger::class, $this->auditSpy);
    }

    public function test_successful_login_emits_login_succeeded_and_token_issued_records(): void
    {
        $user = User::factory()->create([
            'email' => 'login-success@example.com',
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'audit-test',
        ])->assertOk();

        $this->assertTrue($this->auditSpy->hasEvent('login.succeeded'));
        $this->assertTrue($this->auditSpy->hasEvent('token.issued'));
        $this->assertSame($user->id, $this->auditSpy->contextFor('login.succeeded')->userId);
    }

    public function test_failed_login_emits_login_failed_record_with_email_hash_only(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'unknown-user@example.com',
                'password' => 'wrong-password-123',
                'device_name' => 'audit-test',
            ])
            ->assertUnprocessable();

        $context = $this->auditSpy->contextFor('login.failed');
        $this->assertSame(hash('sha256', 'unknown-user@example.com'), $context->emailHash);
        $this->assertNull($context->userId);
        $this->assertSame('198.51.100.20', $context->clientIp);
    }

    public function test_logout_emits_logout_and_token_revoked_with_current_scope(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('browser')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertTrue($this->auditSpy->hasEvent('logout'));
        $this->assertTrue($this->auditSpy->hasEvent('token.revoked'));

        $revokedContext = $this->auditSpy->contextFor('token.revoked');
        $this->assertSame('current', $revokedContext->tokenScope);
        $this->assertSame('logout', $revokedContext->revokeReason);
    }

    public function test_inactive_bearer_emits_token_revoked_with_all_scope_and_inactive_reason(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('browser')->plainTextToken;
        $user->forceFill(['is_active' => false])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        $revokedContext = $this->auditSpy->contextFor('token.revoked');
        $this->assertSame('all', $revokedContext->tokenScope);
        $this->assertSame('inactive_user', $revokedContext->revokeReason);
    }

    public function test_forgot_password_emits_reset_requested_record(): void
    {
        $user = User::factory()->create(['email' => 'forgot-audit@example.com']);

        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        $this->assertTrue($this->auditSpy->hasEvent('password.reset.requested'));
    }

    public function test_reset_password_emits_reset_completed_and_token_revoked_with_all_scope(): void
    {
        $user = User::factory()->create(['email' => 'reset-audit@example.com']);
        $user->createToken('existing-session');

        $token = app('auth.password.broker')->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password-12',
            'password_confirmation' => 'new-secure-password-12',
        ])->assertOk();

        $this->assertTrue($this->auditSpy->hasEvent('password.reset.completed'));
        $this->assertTrue($this->auditSpy->hasEvent('token.revoked'));

        $revokedContext = $this->auditSpy->contextFor('token.revoked');
        $this->assertSame('all', $revokedContext->tokenScope);
        $this->assertSame('password_reset', $revokedContext->revokeReason);
    }

    public function test_email_verification_emits_email_verified_record(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
        );

        $this->getJson($verificationUrl)->assertOk();

        $this->assertTrue($this->auditSpy->hasEvent('email.verified'));
    }

    public function test_audit_context_carries_correlation_id_header(): void
    {
        $user = User::factory()->create();

        $this->withHeader('X-Correlation-Id', 'audit-correlation-123')
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertOk();

        $this->assertSame('audit-correlation-123', $this->auditSpy->contextFor('login.succeeded')->correlationId);
    }

    protected function tearDown(): void
    {
        Auth::forgetGuards();
        parent::tearDown();
    }
}
