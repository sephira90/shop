<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Tests\TestCase;

class ReleaseDocsWorkflowGuardrailTest extends TestCase
{
    public function test_readme_and_release_checklist_reference_canonical_release_aliases(): void
    {
        $readme = (string) file_get_contents(base_path('README.md'));
        $checklist = (string) file_get_contents(base_path('docs/PHASE5_RELEASE_READINESS_CHECKLIST.md'));

        $this->assertStringContainsString('composer run quality:backend', $readme);
        $this->assertStringContainsString('composer run quality:frontend', $readme);
        $this->assertStringContainsString('composer run ops:clear', $readme);
        $this->assertStringContainsString('composer run ops:routes-smoke', $readme);
        $this->assertStringContainsString('composer run ops:observability-report', $readme);
        $this->assertStringContainsString('composer run ops:ci-production-smoke', $readme);
        $this->assertStringContainsString('composer run ops:production-smoke-core', $readme);

        $this->assertStringContainsString('docs/ARCHITECTURE_REFACTOR_NEXT.md', $checklist);
        $this->assertStringContainsString('composer run quality:backend', $checklist);
        $this->assertStringContainsString('composer run quality:frontend', $checklist);
        $this->assertStringContainsString('composer run ops:clear', $checklist);
        $this->assertStringContainsString('composer run ops:routes-smoke', $checklist);
        $this->assertStringContainsString('composer run ops:observability-report', $checklist);
        $this->assertStringContainsString('composer run ops:ci-production-smoke', $checklist);
        $this->assertStringContainsString('composer run ops:production-smoke-core', $checklist);
    }

    public function test_ci_workflow_and_deploy_smoke_script_use_canonical_release_aliases(): void
    {
        $workflow = (string) file_get_contents(base_path('.github/workflows/ci.yml'));
        $deploySmoke = (string) file_get_contents(base_path('deploy/smoke.sh'));

        $this->assertStringContainsString('composer run quality:backend', $workflow);
        $this->assertStringContainsString('composer run quality:frontend', $workflow);
        $this->assertStringContainsString('composer run ops:ci-production-smoke', $workflow);

        $this->assertStringContainsString('composer run ops:production-smoke-core', $deploySmoke);
        $this->assertStringNotContainsString('curl -fsS', $deploySmoke);
    }
}
