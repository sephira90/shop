<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Enforces the C0 Module Boundary Contract for `app/Domains/*`.
 *
 * Rules (see docs/ARCHITECTURE.md → Module Boundary Contract):
 *
 * 1. Cross-module imports go through Contracts only. Within
 *    `app/Domains/<Module>/`, an import of `App\Domains\<OtherModule>\` is
 *    allowed only when the imported namespace is `App\Domains\<OtherModule>\Contracts\`.
 * 2. Module-internal layer direction carries over from the legacy directories:
 *    within a module, controllers must not depend on services/repositories
 *    directly; application handlers must not depend on HTTP transport types;
 *    repositories must not depend on HTTP/service layers.
 * 3. The shared kernel (`App\Domain\*`, `App\Support\*`) and the legacy bridge
 *    namespaces (`App\Contracts\*`, `App\Application\*`, `App\Services\*`,
 *    `App\Repositories\*`, `App\Http\*`, `App\Models\*`) are always importable
 *    during the migration. The allowlist only shrinks as modules relocate.
 *
 * The guardrail passes trivially today (empty `app/Domains/*`) and becomes
 * load-bearing with the first slice moved in C1.
 */
final class ModuleBoundaryGuardrailTest extends TestCase
{
    /**
     * Legacy bridge namespaces importable from any module during migration.
     * This list MUST match the documented allowlist in
     * docs/ARCHITECTURE.md → Module Boundary Contract → Always-allowed namespaces.
     * It only shrinks as modules relocate.
     */
    private const LEGACY_BRIDGE_NAMESPACES = [
        'App\\Domain\\',
        'App\\Support\\',
        'App\\Contracts\\',
        'App\\Application\\',
        'App\\Services\\',
        'App\\Repositories\\',
        'App\\Http\\',
        'App\\Models\\',
        'App\\Exceptions\\',
        'App\\Policies\\',
        'App\\Providers\\',
        'Database\\Factories\\',
        'Database\\Seeders\\',
    ];

    /**
     * Module-internal layer-direction rules. Keyed by module subfolder name
     * (relative to the module root); value is the list of forbidden namespace
     * prefixes within that subfolder.
     *
     * Not used directly today (the per-subfolder rules below are inlined) but
     * kept as documentation of the intended future granularity.
     */
    private const MODULE_ROOT_NAMESPACE_PREFIX = 'App\\Domains\\';

    public function test_legacy_bridge_allowlist_matches_documented_set(): void
    {
        // Lock the allowlist so silent widening is visible. The list mirrors
        // docs/ARCHITECTURE.md → Module Boundary Contract → Always-allowed namespaces.
        $this->assertSame(
            [
                'App\\Domain\\',
                'App\\Support\\',
                'App\\Contracts\\',
                'App\\Application\\',
                'App\\Services\\',
                'App\\Repositories\\',
                'App\\Http\\',
                'App\\Models\\',
                'App\\Exceptions\\',
                'App\\Policies\\',
                'App\\Providers\\',
                'Database\\Factories\\',
                'Database\\Seeders\\',
            ],
            self::LEGACY_BRIDGE_NAMESPACES,
            'LEGACY_BRIDGE_NAMESPACES must match the documented allowlist in docs/ARCHITECTURE.md. Widen the list only via an explicit change to both the constant and the documentation.',
        );
    }

    public function test_cross_module_imports_go_through_contracts_only(): void
    {
        $moduleRoots = $this->discoverModuleRoots();

        foreach ($moduleRoots as $moduleName => $modulePath) {
            $violations = $this->findCrossModuleContractViolations($modulePath, $moduleName);

            $this->assertSame(
                [],
                $violations,
                sprintf(
                    "Module `%s` imports another module's namespace outside of Contracts.\n%s\nCross-module imports must target App\\Domains\\<OtherModule>\\Contracts\\ only.",
                    $moduleName,
                    implode("\n", $violations),
                ),
            );
        }
    }

    public function test_module_internal_layer_direction_for_controllers(): void
    {
        // Within a module, controllers must not depend on Services or Repositories
        // directly; they delegate through Application handlers, mirroring the
        // legacy LayerDependencyDirectionGuardrailTest rule.
        $moduleRoots = $this->discoverModuleRoots();

        foreach ($moduleRoots as $moduleName => $modulePath) {
            $controllerPath = "{$modulePath}/Controllers";

            if (! is_dir($controllerPath)) {
                continue;
            }

            $forbidden = [
                'App\\Domains\\'.$moduleName.'\\Services\\',
                'App\\Domains\\'.$moduleName.'\\Repositories\\',
                'App\\Services\\',
                'App\\Repositories\\',
            ];

            $violations = $this->findForbiddenImports($controllerPath, $forbidden);

            $this->assertSame(
                [],
                $violations,
                sprintf(
                    "Module `%s` controllers must not depend on Services or Repositories directly.\n%s",
                    $moduleName,
                    implode("\n", $violations),
                ),
            );
        }
    }

    public function test_module_internal_layer_direction_for_application_handlers(): void
    {
        $moduleRoots = $this->discoverModuleRoots();

        foreach ($moduleRoots as $moduleName => $modulePath) {
            $applicationPath = "{$modulePath}/Application";

            if (! is_dir($applicationPath)) {
                continue;
            }

            $forbidden = [
                'App\\Http\\Controllers\\',
                'App\\Http\\Requests\\',
            ];

            $violations = $this->findForbiddenImports($applicationPath, $forbidden);

            $this->assertSame(
                [],
                $violations,
                sprintf(
                    "Module `%s` application handlers must not depend on HTTP transport types.\n%s",
                    $moduleName,
                    implode("\n", $violations),
                ),
            );
        }
    }

    public function test_module_internal_layer_direction_for_repositories(): void
    {
        $moduleRoots = $this->discoverModuleRoots();

        foreach ($moduleRoots as $moduleName => $modulePath) {
            $repositoryPath = "{$modulePath}/Repositories";

            if (! is_dir($repositoryPath)) {
                continue;
            }

            $forbidden = [
                'App\\Http\\',
                'App\\Services\\',
            ];

            $violations = $this->findForbiddenImports($repositoryPath, $forbidden);

            $this->assertSame(
                [],
                $violations,
                sprintf(
                    "Module `%s` repositories must stay persistence-only and avoid HTTP/service coupling.\n%s",
                    $moduleName,
                    implode("\n", $violations),
                ),
            );
        }
    }

    /**
     * @return array<string, string> map of module name => absolute path
     */
    private function discoverModuleRoots(): array
    {
        $domainsRoot = app_path('Domains');
        $this->assertDirectoryExists($domainsRoot, 'app/Domains must exist as the modular-monolith root.');

        $modules = [];
        foreach (File::directories($domainsRoot) as $dir) {
            if (! is_string($dir)) {
                continue;
            }
            $name = basename($dir);
            $modules[$name] = $dir;
        }

        $this->assertNotEmpty($modules, 'app/Domains must declare at least one module directory.');

        return $modules;
    }

    /**
     * @param  list<string>  $forbiddenNamespacePrefixes
     * @return list<string> human-readable violation messages
     */
    private function findForbiddenImports(string $directory, array $forbiddenNamespacePrefixes): array
    {
        $violations = [];
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $imports = $this->extractUseStatements((string) File::get($file->getPathname()));

            foreach ($imports as $imported) {
                foreach ($forbiddenNamespacePrefixes as $prefix) {
                    if (str_starts_with($imported, $prefix)) {
                        $violations[] = sprintf(
                            '  %s imports %s — forbidden by module-internal layer direction.',
                            $this->relativePath($file->getPathname()),
                            $imported,
                        );
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * @return list<string> human-readable violation messages
     */
    private function findCrossModuleContractViolations(string $modulePath, string $moduleName): array
    {
        $violations = [];
        $files = File::allFiles($modulePath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $imports = $this->extractUseStatements((string) File::get($file->getPathname()));
            $owningModulePrefix = self::MODULE_ROOT_NAMESPACE_PREFIX.$moduleName.'\\';

            foreach ($imports as $imported) {
                if (! str_starts_with($imported, self::MODULE_ROOT_NAMESPACE_PREFIX)) {
                    continue;
                }

                // Skip imports of the same module (intra-module).
                if (str_starts_with($imported, $owningModulePrefix)) {
                    continue;
                }

                // Cross-module import: must target `<OtherModule>\Contracts\`.
                $afterPrefix = substr($imported, strlen(self::MODULE_ROOT_NAMESPACE_PREFIX));
                $segments = explode('\\', $afterPrefix, 3);

                if (count($segments) >= 2 && $segments[1] === 'Contracts') {
                    continue;
                }

                $violations[] = sprintf(
                    '  %s imports %s — cross-module imports must target App\\Domains\\<OtherModule>\\Contracts\\ only.',
                    $this->relativePath($file->getPathname()),
                    $imported,
                );
            }
        }

        return $violations;
    }

    /**
     * Extracts fully-qualified class names from `use` statements in PHP source.
     *
     * @return list<string>
     */
    private function extractUseStatements(string $source): array
    {
        $imports = [];
        $matches = [];

        // Match `use Some\Foo\Bar;` and `use Some\Foo\Bar as Alias;`. Ignores
        // function/constant use statements (those carry no `\` in the first
        // segment, which we accept as a heuristic since this codebase has none).
        if (! preg_match_all('/^\s*use\s+([A-Za-z_][A-Za-z0-9_\\\\]*)\s*(?:as\s+[A-Za-z_][A-Za-z0-9_]*)?;\s*$/m', $source, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $match) {
            $imports[] = trim($match[1], '\\');
        }

        return $imports;
    }

    private function relativePath(string $absolutePath): string
    {
        $basePath = base_path();

        return str_replace('\\', '/', str_replace($basePath, '', $absolutePath));
    }
}
