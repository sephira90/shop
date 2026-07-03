<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

final class AuthCredentialHardeningGuardrailTest extends TestCase
{
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

    public function test_credential_requests_use_shared_password_default(): void
    {
        foreach ([RegisterRequest::class, ResetPasswordRequest::class] as $requestClass) {
            $filename = (new ReflectionClass($requestClass))->getFileName();
            $this->assertIsString($filename);
            $contents = File::get($filename);

            $this->assertStringContainsString('Password::default()', $contents);
            $this->assertStringNotContainsString("'min:8'", $contents);
        }
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
}
