<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class PsalmLadderScopeParityGuardrailTest extends TestCase
{
    private const PSALM_XML = __DIR__.'/../../../psalm.xml';

    private function psalmXmlContents(): string
    {
        $this->assertFileExists(self::PSALM_XML);

        return (string) File::get(self::PSALM_XML);
    }

    public function test_psalm_error_level_is_four_or_stricter(): void
    {
        $contents = $this->psalmXmlContents();

        $this->assertMatchesRegularExpression(
            '/errorLevel="[1-4]"/',
            $contents,
            'Psalm errorLevel must be 4 or stricter (1-4). Current ladder target is 4.',
        );
    }

    public function test_psalm_scope_covers_app_routes_and_database_layer(): void
    {
        $contents = $this->psalmXmlContents();

        $this->assertStringContainsString('<directory name="app"', $contents, 'Psalm scope must include app/.');
        $this->assertStringContainsString('<directory name="routes"', $contents, 'Psalm scope must include routes/ for PHPStan parity.');
        $this->assertStringContainsString('<directory name="database/factories"', $contents, 'Psalm scope must include database/factories.');
        $this->assertStringContainsString('<directory name="database/seeders"', $contents, 'Psalm scope must include database/seeders.');
    }

    public function test_psalm_laravel_plugin_is_registered(): void
    {
        $contents = $this->psalmXmlContents();

        $this->assertStringContainsString(
            '<pluginClass class="Psalm\LaravelPlugin\Plugin"',
            $contents,
            'psalm/plugin-laravel must be registered in psalm.xml for Eloquent type inference.',
        );
    }

    public function test_psalm_does_not_declare_baseline_file(): void
    {
        $contents = $this->psalmXmlContents();

        $this->assertStringNotContainsString(
            'baselineFile=',
            $contents,
            'Psalm must not declare a baseline file; ladder progression is by source typing, not suppression accumulation.',
        );
        $this->assertStringNotContainsString(
            '<baseline',
            $contents,
            'Psalm must not declare a baseline element; ladder progression is by source typing, not suppression accumulation.',
        );
    }

    public function test_psalm_find_unused_baseline_entry_stays_enabled(): void
    {
        $contents = $this->psalmXmlContents();

        $this->assertStringContainsString(
            'findUnusedBaselineEntry="true"',
            $contents,
            'findUnusedBaselineEntry must stay true so stale suppressions surface.',
        );
    }

    public function test_psalm_version_constraint_satisfies_plugin_compatibility_window(): void
    {
        $composer = (string) File::get(base_path('composer.json'));

        $this->assertMatchesRegularExpression(
            '/"psalm\/plugin-laravel"\s*:\s*"[\^~]/',
            $composer,
            'psalm/plugin-laravel must be declared in require-dev with a range constraint.',
        );
        $this->assertMatchesRegularExpression(
            '/"vimeo\/psalm"\s*:\s*"/',
            $composer,
            'vimeo/psalm must be pinned in require-dev so the plugin compatibility window stays explicit.',
        );
    }

    public function test_psalm_template_arity_suppressions_are_scoped_and_documented(): void
    {
        $contents = $this->psalmXmlContents();

        $this->assertStringContainsString(
            '<TooManyTemplateParams>',
            $contents,
            'TooManyTemplateParams handler must stay present while psalm/plugin-laravel is below v3.14.',
        );
        $this->assertStringContainsString(
            'template-arity divergence',
            $contents,
            'TooManyTemplateParams suppression must carry the documented plugin-version rationale.',
        );
        $this->assertStringContainsString(
            '<InvalidDocblock>',
            $contents,
            'InvalidDocblock handler must stay present while psalm/plugin-laravel is below v3.14.',
        );
    }

    public function test_appservice_provider_uses_environment_over_isproduction(): void
    {
        $provider = (string) File::get(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString(
            "\$this->app->environment('production')",
            $provider,
            'AppServiceProvider must use environment() (declared on the Application contract) instead of isProduction() (concrete-only).',
        );
        $this->assertStringNotContainsString(
            'isProduction(',
            $provider,
            'AppServiceProvider must not rely on the concrete-only isProduction() method.',
        );
    }
}
