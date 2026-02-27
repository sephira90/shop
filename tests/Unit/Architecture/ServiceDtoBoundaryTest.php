<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Tests\Support\Architecture\ArrayPayloadAllowlist;
use Tests\TestCase;

class ServiceDtoBoundaryTest extends TestCase
{
    /**
     * Ensure service layer array payload params stay explicit and allowlisted.
     */
    public function test_service_array_payload_params_are_allowlisted(): void
    {
        $classes = $this->discoverClassesWithPattern(
            app_path('Services'),
            '/function\s+[A-Za-z_][A-Za-z0-9_]*\s*\([^)]*array\s+\$[A-Za-z_][A-Za-z0-9_]*/s',
        );

        $expected = ArrayPayloadAllowlist::serviceArrayPayloadClasses();
        sort($classes);
        sort($expected);

        $this->assertSame($expected, $classes);
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
