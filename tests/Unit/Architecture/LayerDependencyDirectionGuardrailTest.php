<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use SplFileInfo;
use Tests\TestCase;

final class LayerDependencyDirectionGuardrailTest extends TestCase
{
    public function test_application_layer_does_not_depend_on_http_controllers_or_requests(): void
    {
        $this->assertDirectoryFilesDoNotContain(
            app_path('Application'),
            ['use App\\Http\\Controllers\\', 'use App\\Http\\Requests\\'],
            'Application layer must not depend on HTTP controller/request transport types.',
        );
    }

    public function test_service_layer_does_not_depend_on_http_controllers_or_requests(): void
    {
        $this->assertDirectoryFilesDoNotContain(
            app_path('Services'),
            ['use App\\Http\\Controllers\\', 'use App\\Http\\Requests\\'],
            'Service layer must not depend on HTTP controller/request transport types.',
        );
    }

    public function test_repository_layer_does_not_depend_on_http_or_service_layers(): void
    {
        $this->assertDirectoryFilesDoNotContain(
            app_path('Repositories'),
            ['use App\\Http\\', 'use App\\Services\\'],
            'Repository layer must stay persistence-only and avoid HTTP/service coupling.',
        );
    }

    /**
     * @param  list<string>  $forbiddenPatterns
     */
    private function assertDirectoryFilesDoNotContain(
        string $directory,
        array $forbiddenPatterns,
        string $failureMessage,
    ): void {
        $checkedFiles = [];

        /** @var SplFileInfo $file */
        foreach (File::allFiles($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = File::get($file->getPathname());
            $checkedFiles[] = $file->getPathname();

            foreach ($forbiddenPatterns as $pattern) {
                $this->assertStringNotContainsString(
                    $pattern,
                    $contents,
                    $failureMessage.' Offender: '.basename($file->getPathname())." ({$pattern})."
                );
            }
        }

        $this->assertNotEmpty($checkedFiles, "No PHP files found under {$directory} for dependency guardrail.");
    }
}
