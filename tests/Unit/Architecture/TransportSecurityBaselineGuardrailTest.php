<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TransportSecurityBaselineGuardrailTest extends TestCase
{
    private function configPath(string $file): string
    {
        return config_path($file);
    }

    public function test_cors_config_exists_with_api_paths_and_env_driven_origins(): void
    {
        $this->assertFileExists($this->configPath('cors.php'));

        $contents = (string) File::get($this->configPath('cors.php'));

        $this->assertStringContainsString("'paths' => ['api/*']", $contents, 'CORS must scope to API paths only.');
        $this->assertStringContainsString('CORS_ALLOWED_ORIGINS', $contents, 'CORS allowed origins must be env-driven.');
        $this->assertStringContainsString("'supports_credentials' => false", $contents, 'CORS credentials must stay disabled.');
    }

    public function test_security_config_exists_with_force_https_key(): void
    {
        $this->assertFileExists($this->configPath('security.php'));

        $contents = (string) File::get($this->configPath('security.php'));

        $this->assertStringContainsString("'force_https'", $contents, 'security config must declare force_https key.');
        $this->assertStringContainsString('APP_FORCE_HTTPS', $contents, 'force_https must be env-driven via APP_FORCE_HTTPS.');
        $this->assertStringContainsString('APP_TRUSTED_PROXIES', $contents, 'trusted proxies must be env-driven.');
    }

    public function test_session_config_uses_env_with_non_null_default_for_secure_cookie(): void
    {
        $contents = (string) File::get($this->configPath('session.php'));

        $this->assertStringContainsString("env('SESSION_SECURE_COOKIE'", $contents, 'session.secure must read SESSION_SECURE_COOKIE env key.');

        $this->assertStringNotContainsString(
            "env('SESSION_SECURE_COOKIE')",
            $contents,
            'session.secure must declare an explicit non-null default (not bare env() without default).'
        );
    }

    public function test_force_https_middleware_exists_with_env_gates(): void
    {
        $path = app_path('Http/Middleware/ForceHttpsMiddleware.php');

        $this->assertFileExists($path);

        $contents = (string) File::get($path);

        $this->assertStringContainsString("config('security.force_https'", $contents, 'ForceHttps middleware must read the force_https gate.');
        $this->assertStringContainsString("app()->environment('local')", $contents, 'ForceHttps middleware must exempt the local environment.');
    }

    public function test_bootstrap_registers_force_https_middleware_globally(): void
    {
        $contents = (string) File::get(base_path('bootstrap/app.php'));

        $this->assertStringContainsString('ForceHttpsMiddleware', $contents, 'bootstrap/app.php must register ForceHttpsMiddleware globally.');
    }
}
