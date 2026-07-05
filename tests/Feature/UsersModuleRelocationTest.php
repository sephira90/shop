<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Users\Contracts\AccountOrderReadRepository;
use App\Domains\Users\Contracts\AuthAuditLogger;
use App\Domains\Users\Contracts\AuthPasswordBrokerRepository;
use App\Domains\Users\Contracts\AuthUserRepository;
use App\Domains\Users\Infrastructure\ObservabilityAuthAuditLogger;
use App\Domains\Users\Repositories\AccountOrderReadRepository as AccountOrderReadRepositoryImplementation;
use App\Domains\Users\Repositories\AuthPasswordBrokerRepository as AuthPasswordBrokerRepositoryImplementation;
use App\Domains\Users\Repositories\AuthUserRepository as AuthUserRepositoryImplementation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C2 relocation smoke: verifies the Users slice continues to resolve and
 * respond identically after the namespace move into app/Domains/Users/.
 *
 * The detailed wire-contract verification stays in OpenApiConformanceFeatureTest;
 * this test specifically locks the module wiring (provider bindings, contract
 * surfaces, controller namespace, middleware alias) so a regression in the
 * relocation is caught with a focused failure message.
 */
class UsersModuleRelocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_module_contracts_are_bound_to_module_implementations(): void
    {
        $this->assertInstanceOf(
            AuthUserRepositoryImplementation::class,
            $this->app->make(AuthUserRepository::class),
            'AuthUserRepository contract must resolve to the module implementation.',
        );

        $this->assertInstanceOf(
            AuthPasswordBrokerRepositoryImplementation::class,
            $this->app->make(AuthPasswordBrokerRepository::class),
            'AuthPasswordBrokerRepository contract must resolve to the module implementation.',
        );

        $this->assertInstanceOf(
            ObservabilityAuthAuditLogger::class,
            $this->app->make(AuthAuditLogger::class),
            'AuthAuditLogger contract must resolve to the module observability implementation.',
        );

        $this->assertInstanceOf(
            AccountOrderReadRepositoryImplementation::class,
            $this->app->make(AccountOrderReadRepository::class),
            'AccountOrderReadRepository contract must resolve to the module implementation.',
        );
    }

    public function test_auth_controller_resolves_from_module_namespace(): void
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $loginRoute = $routes->getByAction('App\\Domains\\Users\\Controllers\\AuthController@login');

        $this->assertNotNull(
            $loginRoute,
            'AuthController must be registered under the App\\Domains\\Users\\Controllers namespace.',
        );
    }

    public function test_active_api_user_middleware_alias_resolves_to_module_namespace(): void
    {
        $aliases = $this->app->make(\Illuminate\Routing\Router::class)->getMiddleware();

        $this->assertArrayHasKey('active.api.user', $aliases);
        $this->assertSame(
            'App\\Domains\\Users\\Middleware\\EnsureActiveApiUser',
            $aliases['active.api.user'] ?? null,
            'active.api.user alias must resolve to the module middleware.',
        );
    }

    public function test_verification_route_names_are_preserved(): void
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();

        $this->assertNotNull(
            $routes->getByName('verification.verify'),
            'verification.verify route name must be preserved across the module move.',
        );
        $this->assertNotNull(
            $routes->getByName('verification.send'),
            'verification.send route name must be preserved across the module move.',
        );
    }
}
