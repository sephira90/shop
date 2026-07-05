<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Domains\Users\Application\AuthAccessTokenIssuer;
use App\Domains\Users\Application\AuthActiveUserRevalidator;
use App\Domains\Users\Application\Commands\ForgotAuthPasswordHandler;
use App\Domains\Users\Application\Commands\LoginAuthUserHandler;
use App\Domains\Users\Application\Commands\LogoutAuthUserHandler;
use App\Domains\Users\Application\Commands\ResetAuthPasswordHandler;
use App\Domains\Users\Application\Commands\VerifyAuthEmailHandler;
use App\Domains\Users\Contracts\AuthAuditLogger;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionNamedType;
use SplFileInfo;
use Tests\TestCase;

/**
 * F3-79 guardrail: auth audit emission lives behind the application-layer
 * contract and never inside persistence repositories.
 */
final class AuthAuditEmissionGuardrailTest extends TestCase
{
    /**
     * Repositories must stay persistence-only: no audit logging belongs there.
     */
    public function test_repositories_do_not_emit_audit_logs(): void
    {
        foreach ($this->discoverRepositoryFiles() as $file) {
            $contents = File::get($file->getPathname());

            $this->assertStringNotContainsString(
                'AuthAuditLogger',
                $contents,
                "{$file->getFilename()} must not emit auth audit records; audit emission lives in application handlers.",
            );
            $this->assertStringNotContainsString(
                'Log::',
                $contents,
                "{$file->getFilename()} must not use the Log facade; repositories stay persistence-only.",
            );
        }
    }

    /**
     * Every credential-sensitive auth flow must declare the audit logger dependency.
     */
    public function test_credential_sensitive_handlers_depend_on_audit_logger(): void
    {
        $expectedHandlers = [
            LoginAuthUserHandler::class,
            LogoutAuthUserHandler::class,
            ForgotAuthPasswordHandler::class,
            ResetAuthPasswordHandler::class,
            VerifyAuthEmailHandler::class,
            AuthAccessTokenIssuer::class,
            AuthActiveUserRevalidator::class,
        ];

        foreach ($expectedHandlers as $handlerClass) {
            $reflection = new ReflectionClass($handlerClass);
            $constructor = $reflection->getConstructor();
            $this->assertNotNull($constructor, "{$handlerClass} must declare a constructor.");

            $dependencyTypes = [];
            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();
                if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    continue;
                }

                $dependencyTypes[] = $type->getName();
            }

            $this->assertContains(
                AuthAuditLogger::class,
                $dependencyTypes,
                "{$handlerClass} must depend on ".AuthAuditLogger::class.'.',
            );
        }
    }

    /**
     * The audit logger binding resolves to the observability infrastructure implementation.
     */
    public function test_audit_logger_is_bound_to_observability_implementation(): void
    {
        $resolved = $this->app->make(AuthAuditLogger::class);

        $this->assertInstanceOf(
            \App\Domains\Users\Infrastructure\ObservabilityAuthAuditLogger::class,
            $resolved,
        );
    }

    /**
     * @return list<SplFileInfo>
     */
    private function discoverRepositoryFiles(): array
    {
        $files = [];

        $directories = [
            app_path('Repositories'),
            app_path('Domains/Users/Repositories'),
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            foreach (File::allFiles($directory) as $file) {
                if (str_ends_with($file->getFilename(), '.php')) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }
}
