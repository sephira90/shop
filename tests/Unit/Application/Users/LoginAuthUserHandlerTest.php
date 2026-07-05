<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Users;

use App\Contracts\CartServiceInterface;
use App\Domains\Users\Application\AuthApplicationException;
use App\Domains\Users\Application\Commands\LoginAuthUserCommand;
use App\Domains\Users\Application\Commands\LoginAuthUserHandler;
use App\Domains\Users\Application\Dto\LoginAuthInputDto;
use App\Domains\Users\Support\AuthAuditContextResolver;
use App\Domains\Users\Support\AuthUserDtoMapper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\Auth\AuthAuditLoggerSpy;
use Tests\Support\Auth\AuthUserRepositoryFake;
use Tests\TestCase;

final class LoginAuthUserHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_email_still_performs_exactly_one_password_verification_and_emits_failure_audit(): void
    {
        $repository = new AuthUserRepositoryFake(
            findByEmailResult: null,
            passwordValidResult: false,
        );

        /** @var CartServiceInterface $cartService */
        $cartService = $this->app->make(CartServiceInterface::class);

        $auditSpy = new AuthAuditLoggerSpy;

        $handler = new LoginAuthUserHandler(
            $repository,
            $this->app->make(\App\Domains\Users\Application\AuthAccessTokenIssuer::class),
            $cartService,
            $this->app->make(AuthUserDtoMapper::class),
            $auditSpy,
            $this->app->make(AuthAuditContextResolver::class),
        );

        try {
            $handler->handle(new LoginAuthUserCommand(new LoginAuthInputDto(
                email: 'missing@example.com',
                password: 'wrong-password-123',
                deviceName: null,
                guestToken: null,
            )));
            $this->fail('Expected AuthApplicationException was not thrown.');
        } catch (AuthApplicationException $exception) {
            $this->assertSame('Invalid credentials.', $exception->getMessage());
        }

        $this->assertSame(1, $repository->passwordVerificationCount());
        $this->assertTrue($auditSpy->hasEvent('login.failed'));

        $context = $auditSpy->contextFor('login.failed');
        $this->assertNull($context->userId);
        $this->assertSame(hash('sha256', 'missing@example.com'), $context->emailHash);
    }

    public function test_successful_login_emits_succeeded_audit_before_token_issued(): void
    {
        $user = User::factory()->create([
            'email' => 'known@example.com',
            'is_active' => true,
        ]);

        $repository = new AuthUserRepositoryFake(
            findByEmailResult: $user,
            passwordValidResult: true,
        );

        Sanctum::actingAs($user);

        $auditSpy = new AuthAuditLoggerSpy;

        $handler = new LoginAuthUserHandler(
            $repository,
            $this->app->make(\App\Domains\Users\Application\AuthAccessTokenIssuer::class),
            $this->app->make(CartServiceInterface::class),
            $this->app->make(AuthUserDtoMapper::class),
            $auditSpy,
            $this->app->make(AuthAuditContextResolver::class),
        );

        $handler->handle(new LoginAuthUserCommand(new LoginAuthInputDto(
            email: 'known@example.com',
            password: 'password',
            deviceName: null,
            guestToken: null,
        )));

        $this->assertSame(1, $repository->passwordVerificationCount());
        $this->assertTrue($auditSpy->hasEvent('login.succeeded'));
        $this->assertSame($user->id, $auditSpy->contextFor('login.succeeded')->userId);
    }
}
