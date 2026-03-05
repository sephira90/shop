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
        $repoMapPath = base_path('docs/REPO_MAP.md');
        $domainMapPath = base_path('docs/DOMAIN_MAP.md');
        $aiRepoMapAliasPath = base_path('docs/AI_REPO_MAP.md');

        $this->assertTrue(File::exists($architecturePath), 'docs/ARCHITECTURE.md must exist.');
        $this->assertTrue(File::exists($repoMapPath), 'docs/REPO_MAP.md must exist.');
        $this->assertTrue(File::exists($domainMapPath), 'docs/DOMAIN_MAP.md must exist.');
        $this->assertTrue(File::exists($aiRepoMapAliasPath), 'docs/AI_REPO_MAP.md must exist as compatibility alias.');

        $architectureContents = File::get($architecturePath);
        $repoMapContents = File::get($repoMapPath);
        $domainMapContents = File::get($domainMapPath);
        $aiRepoMapAliasContents = File::get($aiRepoMapAliasPath);

        $this->assertStringContainsString('## Layer model', $architectureContents);
        $this->assertStringContainsString('## Dependency rules', $architectureContents);
        $this->assertStringContainsString('## AI entry points', $repoMapContents);
        $this->assertStringContainsString('## Domain map', $repoMapContents);
        $this->assertStringContainsString('## Bounded contexts', $domainMapContents);
        $this->assertStringContainsString('## Domain dependencies', $domainMapContents);
        $this->assertStringContainsString('docs/REPO_MAP.md', $aiRepoMapAliasContents);
        $this->assertStringContainsString('docs/DOMAIN_MAP.md', $aiRepoMapAliasContents);
    }

    public function test_agent_rules_reference_architecture_and_repo_map_docs(): void
    {
        $cursorRules = File::get(base_path('.cursorrules'));
        $agentsRules = File::get(base_path('AGENTS.md'));

        $this->assertStringContainsString('docs/ARCHITECTURE.md', $cursorRules);
        $this->assertStringContainsString('docs/REPO_MAP.md', $cursorRules);
        $this->assertStringContainsString('docs/DOMAIN_MAP.md', $cursorRules);
        $this->assertStringContainsString('docs/ARCHITECTURE.md', $agentsRules);
        $this->assertStringContainsString('docs/REPO_MAP.md', $agentsRules);
        $this->assertStringContainsString('docs/DOMAIN_MAP.md', $agentsRules);
    }
}
