<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Q2 guardrail: the supply-chain audit gate is part of the CI contract and
 * must not regress silently.
 *
 * 1. The CI workflow runs blocking `composer audit` and
 *    `npm audit --omit=dev --audit-level=high` steps so known advisories fail
 *    the build.
 * 2. `.github/dependabot.yml` schedules automated update PRs for the composer,
 *    npm, and github-actions ecosystems.
 * 3. README documents the audit gate and the advisory exception policy
 *    (explicit, dated, removal condition; no audit allowlist).
 * 4. Local audit aliases exist for composer and npm so developers can run the
 *    same checks locally.
 */
final class SupplyChainAuditGateGuardrailTest extends TestCase
{
    public function test_ci_workflow_runs_blocking_dependency_audits(): void
    {
        $workflow = File::get(base_path('.github/workflows/ci.yml'));

        $this->assertStringContainsString(
            'composer audit',
            $workflow,
            'CI must run a blocking composer audit step.',
        );

        $this->assertStringContainsString(
            'npm audit --omit=dev --audit-level=high',
            $workflow,
            'CI must run a blocking npm audit step scoped to non-dev high/critical advisories.',
        );
    }

    public function test_dependabot_config_covers_required_ecosystems(): void
    {
        $this->assertTrue(
            File::exists(base_path('.github/dependabot.yml')),
            '.github/dependabot.yml must exist for automated update PRs.',
        );

        $config = File::get(base_path('.github/dependabot.yml'));

        $this->assertStringContainsString('package-ecosystem: composer', $config);
        $this->assertStringContainsString('package-ecosystem: npm', $config);
        $this->assertStringContainsString('package-ecosystem: github-actions', $config);
        $this->assertStringContainsString('interval: weekly', $config);
    }

    public function test_readme_documents_audit_gate_and_exception_policy(): void
    {
        $readme = File::get(base_path('README.md'));

        $this->assertStringContainsString('composer audit', $readme);
        $this->assertStringContainsString('npm audit --omit=dev --audit-level=high', $readme);
        $this->assertStringContainsString('Advisory exception policy', $readme);
        $this->assertStringContainsString('removal condition', $readme);
        $this->assertStringContainsString('.github/dependabot.yml', $readme);
    }

    public function test_local_audit_aliases_exist_for_composer_and_npm(): void
    {
        $composer = File::get(base_path('composer.json'));
        $package = File::get(base_path('package.json'));

        $this->assertStringContainsString('"audit"', $composer);
        $this->assertStringContainsString('composer audit', $composer);

        $this->assertStringContainsString('"audit"', $package);
        $this->assertStringContainsString('npm audit --omit=dev --audit-level=high', $package);
    }
}
