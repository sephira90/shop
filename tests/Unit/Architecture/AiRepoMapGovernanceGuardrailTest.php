<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class AiRepoMapGovernanceGuardrailTest extends TestCase
{
    public function test_architecture_and_repo_map_docs_exist_with_core_sections(): void
    {
        $architecturePath = base_path('docs/ARCHITECTURE.md');
        $repoMapPath = base_path('docs/AI_REPO_MAP.md');

        $this->assertTrue(File::exists($architecturePath), 'docs/ARCHITECTURE.md must exist.');
        $this->assertTrue(File::exists($repoMapPath), 'docs/AI_REPO_MAP.md must exist.');

        $architectureContents = File::get($architecturePath);
        $repoMapContents = File::get($repoMapPath);

        $this->assertStringContainsString('## Layer model', $architectureContents);
        $this->assertStringContainsString('## Dependency rules', $architectureContents);
        $this->assertStringContainsString('## Entry points by task', $repoMapContents);
        $this->assertStringContainsString('## Bounded context map', $repoMapContents);
    }

    public function test_agent_rules_reference_architecture_and_repo_map_docs(): void
    {
        $cursorRules = File::get(base_path('.cursorrules'));
        $agentsRules = File::get(base_path('AGENTS.md'));

        $this->assertStringContainsString('docs/ARCHITECTURE.md', $cursorRules);
        $this->assertStringContainsString('docs/AI_REPO_MAP.md', $cursorRules);
        $this->assertStringContainsString('docs/ARCHITECTURE.md', $agentsRules);
        $this->assertStringContainsString('docs/AI_REPO_MAP.md', $agentsRules);
    }
}
