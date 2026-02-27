<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

final class LegacyFilterArtifactGuardrailTest extends TestCase
{
    public function test_legacy_filter_namespace_directory_has_no_php_artifacts(): void
    {
        $filterRoot = app_path('Filters');
        if (! is_dir($filterRoot)) {
            $this->assertDirectoryDoesNotExist($filterRoot);

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($filterRoot, FilesystemIterator::SKIP_DOTS)
        );

        $legacyFilterPhpFiles = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $legacyFilterPhpFiles[] = $file->getPathname();
        }

        $this->assertSame([], $legacyFilterPhpFiles);
    }
}
