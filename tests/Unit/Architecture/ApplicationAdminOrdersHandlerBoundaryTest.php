<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionNamedType;
use SplFileInfo;
use Tests\TestCase;

class ApplicationAdminOrdersHandlerBoundaryTest extends TestCase
{
    /**
     * Ensure admin order handlers do not return ORM models or paginator contracts.
     */
    public function test_admin_order_handlers_do_not_return_orm_or_paginator_types(): void
    {
        $handlers = $this->discoverAdminOrderHandlerClasses();
        $this->assertNotEmpty($handlers, 'No admin order handlers found for architecture guardrail.');

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
                "{$handlerClass}::handle() must return application DTO/value boundary, not paginator."
            );
        }
    }

    /**
     * @return list<class-string>
     */
    private function discoverAdminOrderHandlerClasses(): array
    {
        $directories = [
            [
                'path' => app_path('Application/Admin/Orders/Commands'),
                'namespace' => 'App\\Application\\Admin\\Orders\\Commands\\',
            ],
            [
                'path' => app_path('Application/Admin/Orders/Queries'),
                'namespace' => 'App\\Application\\Admin\\Orders\\Queries\\',
            ],
        ];

        $handlerClasses = [];

        foreach ($directories as $definition) {
            /** @var string $handlersDirectory */
            $handlersDirectory = $definition['path'];
            /** @var string $namespacePrefix */
            $namespacePrefix = $definition['namespace'];

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

                $handlerClass = $namespacePrefix.$relativePath;

                if (! class_exists($handlerClass)) {
                    $this->fail("Admin order handler class {$handlerClass} could not be autoloaded.");
                }

                $handlerClasses[] = $handlerClass;
            }
        }

        sort($handlerClasses);

        return array_values(array_unique($handlerClasses));
    }
}
