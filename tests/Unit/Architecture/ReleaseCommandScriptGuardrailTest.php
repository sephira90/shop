<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Support\Data\TypedValue;
use Tests\TestCase;

class ReleaseCommandScriptGuardrailTest extends TestCase
{
    public function test_composer_release_and_quality_aliases_are_defined_with_expected_sequences(): void
    {
        /** @var array<string, mixed> $composer */
        $composer = TypedValue::associativeArray(json_decode((string) file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR));
        /** @var array<string, mixed> $scripts */
        $scripts = TypedValue::associativeArray($composer['scripts'] ?? []);

        $this->assertSame([
            '@lint',
            '@analyse',
            '@test',
        ], $scripts['quality:backend'] ?? null);

        $this->assertSame([
            'npm run lint',
            'npm run lint:ox',
            'npm run format:ox:check',
            'npm run type-check',
            'npm run test',
            'npm run build',
        ], $scripts['quality:frontend'] ?? null);

        $this->assertSame([
            '@php artisan app:healthcheck',
        ], $scripts['ops:healthcheck'] ?? null);

        $this->assertSame([
            '@ops:healthcheck',
            '@ops:performance-smoke',
            '@ops:webhook-flow-smoke',
            '@ops:api-contract-smoke',
            '@ops:observability-report',
        ], $scripts['ops:production-smoke-core'] ?? null);

        $this->assertSame([
            '@ops:clear',
            '@ops:routes-smoke',
            '@ops:production-smoke-core',
        ], $scripts['ops:ci-production-smoke'] ?? null);

        $this->assertSame([
            '@quality:backend',
        ], $scripts['quality'] ?? null);
    }
}
