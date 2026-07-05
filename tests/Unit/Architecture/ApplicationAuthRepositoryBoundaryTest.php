<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Domains\Users\Contracts\AuthPasswordBrokerRepository as AuthPasswordBrokerRepositoryContract;
use App\Domains\Users\Contracts\AuthUserRepository as AuthUserRepositoryContract;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionNamedType;
use SplFileInfo;
use Tests\TestCase;

class ApplicationAuthRepositoryBoundaryTest extends TestCase
{
    /**
     * Ensure auth handlers avoid direct persistence facades and static ORM queries.
     */
    public function test_auth_handlers_do_not_use_direct_persistence_access(): void
    {
        foreach ($this->discoverAuthHandlerFiles() as $file) {
            $contents = File::get($file->getPathname());

            $this->assertStringNotContainsString(
                'User::query(',
                $contents,
                "{$file->getFilename()} must use auth repository contract instead of User::query()."
            );

            $this->assertStringNotContainsString(
                'Password::sendResetLink(',
                $contents,
                "{$file->getFilename()} must use auth password broker repository contract."
            );

            $this->assertStringNotContainsString(
                'Password::reset(',
                $contents,
                "{$file->getFilename()} must use auth password broker repository contract."
            );
        }
    }

    /**
     * Ensure auth handlers declare repository contract dependencies for persistence flows.
     */
    public function test_auth_handlers_depend_on_repository_contracts(): void
    {
        $expectedDependencies = [
            'App\\Domains\\Users\\Application\\Commands\\RegisterAuthUserHandler' => AuthUserRepositoryContract::class,
            'App\\Domains\\Users\\Application\\Commands\\LoginAuthUserHandler' => AuthUserRepositoryContract::class,
            'App\\Domains\\Users\\Application\\Commands\\LogoutAuthUserHandler' => AuthUserRepositoryContract::class,
            'App\\Domains\\Users\\Application\\Commands\\UpdateAuthProfileHandler' => AuthUserRepositoryContract::class,
            'App\\Domains\\Users\\Application\\Commands\\ResendAuthVerificationHandler' => AuthUserRepositoryContract::class,
            'App\\Domains\\Users\\Application\\Commands\\VerifyAuthEmailHandler' => AuthUserRepositoryContract::class,
            'App\\Domains\\Users\\Application\\Commands\\ForgotAuthPasswordHandler' => AuthPasswordBrokerRepositoryContract::class,
            'App\\Domains\\Users\\Application\\Commands\\ResetAuthPasswordHandler' => AuthPasswordBrokerRepositoryContract::class,
        ];

        foreach ($expectedDependencies as $handlerClass => $expectedContract) {
            if (! class_exists($handlerClass)) {
                $this->fail("Auth handler class {$handlerClass} could not be autoloaded.");
            }

            $reflection = new ReflectionClass($handlerClass);
            $constructor = $reflection->getConstructor();
            $this->assertNotNull($constructor, "{$handlerClass} must declare constructor with repository contract dependency.");

            $dependencyTypes = [];
            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();
                if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    continue;
                }

                $dependencyTypes[] = $type->getName();
            }

            $this->assertContains(
                $expectedContract,
                $dependencyTypes,
                "{$handlerClass} must depend on {$expectedContract}."
            );
        }
    }

    /**
     * @return list<SplFileInfo>
     */
    private function discoverAuthHandlerFiles(): array
    {
        $directories = [
            app_path('Domains/Users/Application/Commands'),
            app_path('Domains/Users/Application/Queries'),
        ];

        $files = [];

        foreach ($directories as $directory) {
            /** @var SplFileInfo $file */
            foreach (File::allFiles($directory) as $file) {
                if (! str_ends_with($file->getFilename(), 'Handler.php')) {
                    continue;
                }

                $files[] = $file;
            }
        }

        return $files;
    }
}
