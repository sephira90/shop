<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class DocumentationAuthorityGuardrailTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const ARCHIVED_PLAN_PATHS = [
        'docs/ARCHITECTURE_REFACTOR_PLAN.md',
        'docs/DEEP_REFACTORING_PLAN.md',
        'docs/DTO_IMPLEMENTATION_PLAN.md',
        'docs/TEMPLATE_COMPONENTIZATION_PLAN.md',
    ];

    public function test_archived_plans_keep_explicit_archival_banners(): void
    {
        foreach (self::ARCHIVED_PLAN_PATHS as $path) {
            $contents = File::get(base_path($path));

            $this->assertStringContainsString('Historical plan', $contents, "{$path} must stay explicitly archived.");
            $this->assertStringContainsString(
                'Do not use as the active execution authority.',
                $contents,
                "{$path} must explicitly forbid active-use drift."
            );
            $this->assertStringContainsString(
                'docs/ARCHITECTURE_REFACTOR_NEXT.md',
                $contents,
                "{$path} must point back to the active roadmap."
            );
            $this->assertStringNotContainsString(
                'Status: `Active`',
                $contents,
                "{$path} must not look like an active plan."
            );
        }
    }

    public function test_execution_log_keeps_non_authoritative_banner(): void
    {
        $contents = File::get(base_path('docs/REFACTORING_EXECUTION_PLAN.md'));

        $this->assertStringContainsString('Operational execution log only.', $contents);
        $this->assertStringContainsString('docs/ARCHITECTURE_REFACTOR_NEXT.md', $contents);
    }
}
