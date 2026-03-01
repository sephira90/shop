<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

final class LegacyTransportResourceArtifactGuardrailTest extends TestCase
{
    public function test_legacy_http_resource_namespace_has_no_php_artifacts(): void
    {
        $resourceRoot = app_path('Http/Resources');
        if (! is_dir($resourceRoot)) {
            $this->assertDirectoryDoesNotExist($resourceRoot);

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resourceRoot, FilesystemIterator::SKIP_DOTS)
        );

        $resourcePhpFiles = [];

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }

            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $resourcePhpFiles[] = $file->getPathname();
        }

        $this->assertSame([], $resourcePhpFiles);
    }
}
