<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Tests\Support\Architecture\ArrayPayloadAllowlist;
use Tests\TestCase;

class ApplicationDtoBoundaryTest extends TestCase
{
    /**
     * Ensure application payload arrays are explicit and allowlisted only.
     */
    public function test_application_payload_arrays_are_allowlisted(): void
    {
        $classes = $this->discoverClassesWithPattern(
            app_path('Application'),
            '/public\s+array\s+\$(payload|filters)\b/',
        );

        $expected = ArrayPayloadAllowlist::applicationArrayPayloadClasses();
        sort($classes);
        sort($expected);

        $this->assertSame($expected, $classes);
        $this->assertLessThanOrEqual(
            ArrayPayloadAllowlist::BASELINE_APPLICATION_ARRAY_PAYLOAD_COUNT,
            count($classes),
        );
    }

    /**
     * Ensure array return from handler is explicit and allowlisted only.
     */
    public function test_application_handle_array_returns_are_allowlisted(): void
    {
        $classes = $this->discoverClassesWithPattern(
            app_path('Application'),
            '/function\s+handle\s*\([^)]*\)\s*:\s*array/s',
        );

        $expected = ArrayPayloadAllowlist::applicationHandleArrayReturnClasses();
        sort($classes);
        sort($expected);

        $this->assertSame($expected, $classes);
        $this->assertLessThanOrEqual(
            ArrayPayloadAllowlist::BASELINE_APPLICATION_HANDLE_ARRAY_RETURN_COUNT,
            count($classes),
        );
    }

    /**
     * @return array<int, class-string>
     */
    private function discoverClassesWithPattern(string $directory, string $pattern): array
    {
        $classes = [];

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (! is_string($contents) || preg_match($pattern, $contents) !== 1) {
                continue;
            }

            $className = $this->extractClassName($contents);
            if ($className !== null) {
                $classes[] = $className;
            }
        }

        return array_values(array_unique($classes));
    }

    /**
     * @return class-string|null
     */
    private function extractClassName(string $contents): ?string
    {
        $namespaceMatch = [];
        $classMatch = [];

        if (
            preg_match('/namespace\s+([^;]+);/', $contents, $namespaceMatch) !== 1
            || preg_match('/class\s+([A-Za-z_][A-Za-z0-9_]*)/', $contents, $classMatch) !== 1
        ) {
            return null;
        }

        return trim($namespaceMatch[1]).'\\'.trim($classMatch[1]);
    }
}
