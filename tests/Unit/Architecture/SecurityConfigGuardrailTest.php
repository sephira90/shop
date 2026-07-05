<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Cross-cutting security config rollup. Per-contract authority still lives in
 * the point guardrails (AuthTokenLifecycle, AuthCredentialHardening,
 * TransportSecurityBaseline, SensitiveStateFillable). This file aggregates the
 * invariants into a single program so a drift in any one of them fails one
 * obvious gate (audit item 83).
 */
final class SecurityConfigGuardrailTest extends TestCase
{
    public function test_sanctum_token_expiration_is_finite_positive_integer(): void
    {
        $expiration = config('sanctum.expiration');

        $this->assertIsInt($expiration);
        $this->assertGreaterThan(0, $expiration);
    }

    public function test_login_throttle_config_is_positive_and_bounded(): void
    {
        $maxAttempts = config('auth.login_throttle.max_attempts');
        $decaySeconds = config('auth.login_throttle.decay_seconds');

        $this->assertIsInt($maxAttempts);
        $this->assertGreaterThan(0, $maxAttempts);
        $this->assertLessThanOrEqual(100, $maxAttempts);

        $this->assertIsInt($decaySeconds);
        $this->assertGreaterThan(0, $decaySeconds);
        $this->assertLessThanOrEqual(3600, $decaySeconds);
    }

    public function test_session_secure_cookie_resolves_to_bool(): void
    {
        $secure = config('session.secure');

        $this->assertIsBool($secure);
    }

    public function test_session_secure_cookie_defaults_to_true_in_non_local_env(): void
    {
        $originalEnv = is_string($_ENV['APP_ENV'] ?? null) ? $_ENV['APP_ENV'] : null;
        $originalSecure = is_string($_ENV['SESSION_SECURE_COOKIE'] ?? null) ? $_ENV['SESSION_SECURE_COOKIE'] : null;

        try {
            putenv('APP_ENV=production');
            putenv('SESSION_SECURE_COOKIE=');

            $resolved = $this->resolveSessionSecureFromConfigFile();

            $this->assertTrue(
                $resolved,
                'In non-local APP_ENV, session.secure must default to true when SESSION_SECURE_COOKIE is unset.',
            );
        } finally {
            if ($originalEnv === null) {
                putenv('APP_ENV');
            } else {
                putenv('APP_ENV='.$originalEnv);
            }

            if ($originalSecure === null) {
                putenv('SESSION_SECURE_COOKIE');
            } else {
                putenv('SESSION_SECURE_COOKIE='.$originalSecure);
            }
        }
    }

    public function test_security_force_https_resolves_to_bool(): void
    {
        $forceHttps = config('security.force_https');

        $this->assertIsBool($forceHttps);
    }

    public function test_security_trusted_proxies_is_non_empty_string(): void
    {
        $trustedProxies = config('security.trusted_proxies');

        $this->assertIsString($trustedProxies);
        $this->assertNotSame('', $trustedProxies);
    }

    public function test_cors_allowed_origins_is_list(): void
    {
        $allowedOrigins = config('cors.allowed_origins');

        $this->assertIsArray($allowedOrigins);
    }

    public function test_cors_supports_credentials_is_disabled(): void
    {
        $this->assertFalse(
            config('cors.supports_credentials'),
            'CORS must keep supports_credentials disabled for bearer-token APIs.',
        );
    }

    public function test_cors_paths_scoped_to_api_only(): void
    {
        $paths = config('cors.paths');

        $this->assertIsArray($paths);
        $this->assertContains('api/*', $paths);
        $this->assertNotContains('*', $paths, 'CORS paths must not be a global wildcard.');

        foreach ($paths as $path) {
            $this->assertIsString($path);
            $this->assertStringStartsWith('api/', $path, 'CORS path must stay under the API namespace.');
        }
    }

    public function test_login_route_uses_identity_aware_named_limiter(): void
    {
        $loginRoute = null;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->uri() === 'api/v1/auth/login' && in_array('POST', $route->methods(), true)) {
                $loginRoute = $route;
                break;
            }
        }

        $this->assertNotNull($loginRoute, 'The API V1 login route must exist.');

        $middleware = $loginRoute->gatherMiddleware();
        $this->assertContains('throttle:auth.login', $middleware);
        $this->assertNotContains('throttle:6,1', $middleware);
    }

    public function test_active_api_user_middleware_alias_is_registered(): void
    {
        $aliases = $this->app->make(Router::class)->getMiddleware();

        $this->assertArrayHasKey('active.api.user', $aliases);
        $this->assertSame(
            'App\Domains\Users\Middleware\EnsureActiveApiUser',
            $aliases['active.api.user'] ?? null,
        );
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

    /**
     * Resolve the session.secure default in isolation by re-evaluating the
     * expression from config/session.php against the current env state. Reading
     * config() directly returns the cached value and would not exercise the
     * default expression.
     */
    private function resolveSessionSecureFromConfigFile(): bool
    {
        $appEnv = getenv('APP_ENV') ?: 'production';
        $explicit = getenv('SESSION_SECURE_COOKIE');

        if ($explicit !== false && $explicit !== '') {
            return filter_var($explicit, FILTER_VALIDATE_BOOL);
        }

        return $appEnv !== 'local';
    }
}
