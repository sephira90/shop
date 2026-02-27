<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionNamedType;
use SplFileInfo;
use Tests\TestCase;

class PublicApiControllerArchitectureTest extends TestCase
{
    /**
     * Ensure public API controllers depend only on application-layer handlers.
     */
    public function test_public_api_controllers_depend_on_application_handlers_only(): void
    {
        $controllers = $this->discoverApiV1ControllerClasses();
        $this->assertNotEmpty($controllers, 'No API V1 controllers found for architecture guardrail.');

        foreach ($controllers as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);
            $constructor = $reflection->getConstructor();

            $this->assertNotNull($constructor, "{$controllerClass} must define constructor dependencies.");

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                $this->assertInstanceOf(
                    ReflectionNamedType::class,
                    $type,
                    "{$controllerClass} constructor parameter {$parameter->getName()} must have a named type."
                );
                $this->assertFalse(
                    $type->isBuiltin(),
                    "{$controllerClass} constructor parameter {$parameter->getName()} must be a class type."
                );

                $dependency = $type->getName();

                $this->assertStringStartsWith(
                    'App\\Application\\',
                    $dependency,
                    "{$controllerClass} must not depend on repository/service layer directly."
                );
                $this->assertStringEndsWith(
                    'Handler',
                    $dependency,
                    "{$controllerClass} constructor dependencies must be handler classes."
                );
            }
        }
    }

    /**
     * @return list<class-string>
     */
    private function discoverApiV1ControllerClasses(): array
    {
        $controllerDirectory = app_path('Http/Controllers/Api/V1');
        $controllerClasses = [];

        /** @var SplFileInfo $file */
        foreach (File::allFiles($controllerDirectory) as $file) {
            $relativePath = str_replace(
                [$controllerDirectory.DIRECTORY_SEPARATOR, '.php', DIRECTORY_SEPARATOR],
                ['', '', '\\'],
                $file->getPathname()
            );
            $controllerClass = 'App\\Http\\Controllers\\Api\\V1\\'.$relativePath;

            if (! class_exists($controllerClass)) {
                $this->fail("Controller class {$controllerClass} could not be autoloaded.");
            }

            $controllerClasses[] = $controllerClass;
        }

        sort($controllerClasses);

        return $controllerClasses;
    }
}
