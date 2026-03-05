<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class ModularMonolithSkeletonGuardrailTest extends TestCase
{
    public function test_domain_module_skeleton_directories_exist(): void
    {
        $domains = [
            'Catalog',
            'Cart',
            'Checkout',
            'Orders',
            'Users',
            'Payments',
            'Webhooks',
        ];

        foreach ($domains as $domain) {
            $domainPath = app_path("Domains/{$domain}");
            $readmePath = "{$domainPath}/README.md";

            $this->assertDirectoryExists(
                $domainPath,
                "Missing modular-monolith domain directory: app/Domains/{$domain}"
            );
            $this->assertTrue(
                File::exists($readmePath),
                "Missing domain module README: app/Domains/{$domain}/README.md"
            );

            $readmeContents = File::get($readmePath);
            $this->assertStringContainsString("# {$domain} Domain Module", $readmeContents);
        }
    }

    public function test_architecture_docs_keep_modular_monolith_target_layout_section(): void
    {
        $architectureContents = File::get(base_path('docs/ARCHITECTURE.md'));
        $repoMapContents = File::get(base_path('docs/REPO_MAP.md'));

        $this->assertStringContainsString('## Modular Monolith Target Layout', $architectureContents);
        $this->assertStringContainsString('app/', $architectureContents);
        $this->assertStringContainsString('Domains/', $architectureContents);

        $this->assertStringContainsString('## Target layout', $repoMapContents);
        $this->assertStringContainsString('app/', $repoMapContents);
        $this->assertStringContainsString('Domains/', $repoMapContents);
    }
}
