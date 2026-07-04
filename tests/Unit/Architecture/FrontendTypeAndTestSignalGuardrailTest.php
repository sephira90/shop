<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class FrontendTypeAndTestSignalGuardrailTest extends TestCase
{
    private function tsconfigContents(): string
    {
        $path = base_path('tsconfig.json');
        $this->assertFileExists($path);

        return (string) File::get($path);
    }

    private function vitestConfigContents(): string
    {
        $path = base_path('vitest.config.ts');
        $this->assertFileExists($path);

        return (string) File::get($path);
    }

    private function packageJsonContents(): string
    {
        $path = base_path('package.json');
        $this->assertFileExists($path);

        return (string) File::get($path);
    }

    public function test_tsconfig_enables_strict_with_low_churn_hardening_flags(): void
    {
        $contents = $this->tsconfigContents();

        $this->assertStringContainsString('"strict": true', $contents, 'tsconfig must keep strict: true.');
        $this->assertStringContainsString('"noImplicitOverride": true', $contents, 'tsconfig must enable noImplicitOverride for override-keyword enforcement.');
        $this->assertStringContainsString('"noFallthroughCasesInSwitch": true', $contents, 'tsconfig must enable noFallthroughCasesInSwitch to forbid fall-through switch cases.');
    }

    public function test_tsconfig_does_not_enable_no_unchecked_indexed_access_yet(): void
    {
        // Locked absent per Q4 Slice 2 decision: the flag surfaced 55 errors (2 production,
        // 53 test-only) and was deferred to a follow-up that first tightens test fixtures
        // to typed factories. When the follow-up lands, this assertion flips to present.

        $contents = $this->tsconfigContents();

        $this->assertStringNotContainsString(
            '"noUncheckedIndexedAccess": true',
            $contents,
            'noUncheckedIndexedAccess must stay deferred (Q4 Slice 2) until the test-fixture tightening follow-up lands.',
        );
    }

    public function test_vitest_config_declares_v8_coverage_provider_with_html_reporter(): void
    {
        $contents = $this->vitestConfigContents();

        $this->assertStringContainsString('provider: "v8"', $contents, 'vitest coverage provider must be v8.');
        $this->assertStringContainsString('"html"', $contents, 'vitest coverage must emit html for local inspection.');
        $this->assertStringContainsString('"text"', $contents, 'vitest coverage must emit text summary for CI logs.');
        $this->assertStringContainsString('reportsDirectory: "coverage/"', $contents, 'vitest coverage reports directory must be coverage/.');
        $this->assertStringContainsString('all: true', $contents, 'vitest coverage must include untested files (all: true) for an honest baseline.');
    }

    public function test_package_json_declares_coverage_script_and_provider_dependency(): void
    {
        $contents = $this->packageJsonContents();

        $this->assertStringContainsString('"test:coverage": "vitest run --coverage"', $contents, 'package.json must declare test:coverage script.');
        $this->assertStringContainsString('"test": "vitest run"', $contents, 'package.json must keep test script unchanged (no implicit coverage).');
        $this->assertMatchesRegularExpression(
            '/"@vitest\/coverage-v8"\s*:\s*"\^/',
            $contents,
            '@vitest/coverage-v8 must be in devDependencies with a range constraint aligned with vitest.',
        );
    }

    public function test_gitignore_excludes_coverage_directory(): void
    {
        $contents = (string) File::get(base_path('.gitignore'));

        $this->assertStringContainsString('/coverage', $contents, '.gitignore must exclude /coverage so reports do not leak into the repository.');
    }
}
