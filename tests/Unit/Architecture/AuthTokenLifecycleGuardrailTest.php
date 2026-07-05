<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Domains\Users\Contracts\AuthUserRepository;
use App\Domains\Users\Middleware\EnsureActiveApiUser;
use DateTimeInterface;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Tests\TestCase;

final class AuthTokenLifecycleGuardrailTest extends TestCase
{
    public function test_sanctum_token_expiration_is_finite(): void
    {
        $expiration = config('sanctum.expiration');

        $this->assertIsInt($expiration);
        $this->assertGreaterThan(0, $expiration);
    }

    public function test_active_api_user_middleware_alias_is_registered(): void
    {
        $aliases = $this->app->make(Router::class)->getMiddleware();

        $this->assertArrayHasKey('active.api.user', $aliases);
        $this->assertSame(EnsureActiveApiUser::class, $aliases['active.api.user'] ?? null);
    }

    public function test_authenticated_api_routes_revalidate_active_users(): void
    {
        $authSantumRoutes = $this->collectRoutes(
            static fn (array $middleware): bool => in_array('auth:sanctum', $middleware, true),
        );

        $this->assertNotEmpty($authSantumRoutes, 'Expected at least one api/v1 route guarded by auth:sanctum.');

        foreach ($authSantumRoutes as $label => $middleware) {
            $this->assertContains(
                'active.api.user',
                $middleware,
                "{$label} uses auth:sanctum and must also use the active.api.user middleware.",
            );
        }
    }

    public function test_optional_auth_cart_and_checkout_routes_revalidate_active_users(): void
    {
        $optionalAuthRoutes = $this->collectRoutes(
            static fn (array $middleware, string $uri): bool => ! in_array('auth:sanctum', $middleware, true)
                && (str_starts_with($uri, 'api/v1/cart') || $uri === 'api/v1/checkout/place-order'),
        );

        $this->assertNotEmpty(
            $optionalAuthRoutes,
            'Expected guest-capable cart and checkout place-order routes to be registered.',
        );

        foreach ($optionalAuthRoutes as $label => $middleware) {
            $this->assertContains(
                'active.api.user',
                $middleware,
                "{$label} is guest-capable and must still use the active.api.user middleware.",
            );
        }
    }

    public function test_auth_repository_keeps_current_and_global_revoke_contracts_explicit(): void
    {
        $reflection = new ReflectionClass(AuthUserRepository::class);

        $this->assertTrue($reflection->hasMethod('revokeCurrentAccessToken'));
        $this->assertTrue($reflection->hasMethod('revokeAllAccessTokens'));

        $issueMethod = $reflection->getMethod('issueAccessToken');
        $expiresAtParameter = null;

        foreach ($issueMethod->getParameters() as $parameter) {
            if ($parameter->getName() === 'expiresAt') {
                $expiresAtParameter = $parameter;
                break;
            }
        }

        $this->assertInstanceOf(ReflectionParameter::class, $expiresAtParameter, 'issueAccessToken must declare an expiresAt parameter.');

        $expiresAtType = $expiresAtParameter->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $expiresAtType);
        $this->assertSame(DateTimeInterface::class, $expiresAtType->getName());
    }

    /**
     * @param  callable(array<int, string>, string): bool  $predicate
     * @return array<string, array<int, string>>
     */
    private function collectRoutes(callable $predicate): array
    {
        $matched = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/v1/')) {
                continue;
            }

            /** @var array<int, string> $middleware */
            $middleware = $route->gatherMiddleware();

            if ($predicate($middleware, $uri)) {
                $matched[$uri.' '.$route->getActionMethod()] = $middleware;
            }
        }

        return $matched;
    }
}
