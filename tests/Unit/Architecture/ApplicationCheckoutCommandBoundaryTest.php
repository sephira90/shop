<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionNamedType;
use SplFileInfo;
use Tests\TestCase;

class ApplicationCheckoutCommandBoundaryTest extends TestCase
{
    /**
     * Ensure checkout command handlers do not return ORM models.
     */
    public function test_checkout_command_handlers_do_not_return_orm_models(): void
    {
        $handlers = $this->discoverCheckoutCommandHandlerClasses();
        $this->assertNotEmpty($handlers, 'No checkout command handlers found for architecture guardrail.');

        foreach ($handlers as $handlerClass) {
            $reflection = new ReflectionClass($handlerClass);
            $this->assertTrue($reflection->hasMethod('handle'), "{$handlerClass} must define handle().");

            $handleMethod = $reflection->getMethod('handle');
            $returnType = $handleMethod->getReturnType();

            $this->assertInstanceOf(
                ReflectionNamedType::class,
                $returnType,
                "{$handlerClass}::handle() must define a named return type."
            );

            if ($returnType->isBuiltin()) {
                continue;
            }

            $returnTypeName = $returnType->getName();

            $this->assertStringStartsNotWith(
                'App\\Models\\',
                $returnTypeName,
                "{$handlerClass}::handle() must return application DTO/value boundary, not ORM model."
            );
        }
    }

    /**
     * @return list<class-string>
     */
    private function discoverCheckoutCommandHandlerClasses(): array
    {
        $handlersDirectory = app_path('Application/Checkout/Commands');
        $handlerClasses = [];

        /** @var SplFileInfo $file */
        foreach (File::allFiles($handlersDirectory) as $file) {
            if (! str_ends_with($file->getFilename(), 'Handler.php')) {
                continue;
            }

            $relativePath = str_replace(
                [$handlersDirectory.DIRECTORY_SEPARATOR, '.php', DIRECTORY_SEPARATOR],
                ['', '', '\\'],
                $file->getPathname()
            );

            $handlerClass = 'App\\Application\\Checkout\\Commands\\'.$relativePath;
            if (! class_exists($handlerClass)) {
                $this->fail("Checkout command handler class {$handlerClass} could not be autoloaded.");
            }

            $handlerClasses[] = $handlerClass;
        }

        sort($handlerClasses);

        return $handlerClasses;
    }
}
