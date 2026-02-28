<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionNamedType;
use SplFileInfo;
use Tests\TestCase;

class ApplicationHandlerBoundaryTest extends TestCase
{
    /**
     * Ensure application handlers do not return ORM models or persistence collections/contracts.
     */
    public function test_application_handlers_do_not_return_orm_or_persistence_boundaries(): void
    {
        $handlers = $this->discoverApplicationHandlerClasses();
        $this->assertNotEmpty($handlers, 'No application handlers found for architecture guardrail.');

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

            $this->assertNotSame(
                'Illuminate\\Contracts\\Pagination\\LengthAwarePaginator',
                $returnTypeName,
                "{$handlerClass}::handle() must return application DTO/value boundary, not paginator contract."
            );

            $this->assertNotSame(
                'Illuminate\\Database\\Eloquent\\Collection',
                $returnTypeName,
                "{$handlerClass}::handle() must return application DTO/value boundary, not Eloquent collection."
            );
        }
    }

    /**
     * @return list<class-string>
     */
    private function discoverApplicationHandlerClasses(): array
    {
        $handlersDirectory = app_path('Application');
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

            $handlerClass = 'App\\Application\\'.$relativePath;

            if (! class_exists($handlerClass)) {
                $this->fail("Application handler class {$handlerClass} could not be autoloaded.");
            }

            $handlerClasses[] = $handlerClass;
        }

        sort($handlerClasses);

        return array_values(array_unique($handlerClasses));
    }
}
