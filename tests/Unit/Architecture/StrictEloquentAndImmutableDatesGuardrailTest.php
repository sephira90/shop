<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Providers\AppServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

/**
 * Q1 guardrail: strict Eloquent runtime mode and immutable date wiring are
 * part of the architectural contract and must not regress silently.
 *
 * 1. AppServiceProvider owns the runtime wiring for both invariants in a
 *    single boot-time point.
 * 2. Strict mode is gated on the non-production environment so production
 *    behavior stays unchanged (Q1 DoD).
 * 3. The global Date resolver resolves to CarbonImmutable so model timestamp
 *    attributes and `now()` share the immutable contract.
 * 4. No Eloquent model ships a mutable `datetime`/`timestamp` cast; the
 *    allowlist only shrinks from here.
 */
final class StrictEloquentAndImmutableDatesGuardrailTest extends TestCase
{
    public function test_app_service_provider_wires_strict_mode_and_immutable_dates(): void
    {
        $source = File::get(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString(
            'Model::shouldBeStrict(! $this->app->isProduction());',
            $source,
            'Strict Eloquent mode must be wired in AppServiceProvider::boot(), gated on non-production.',
        );

        $this->assertStringContainsString(
            'Date::use(CarbonImmutable::class);',
            $source,
            'Immutable date resolver must be wired in AppServiceProvider::boot().',
        );
    }

    public function test_app_service_provider_boot_is_owned_and_declares_required_imports(): void
    {
        $reflection = new ReflectionClass(AppServiceProvider::class);

        $this->assertSame(
            AppServiceProvider::class,
            $reflection->getMethod('boot')->getDeclaringClass()->getName(),
            'AppServiceProvider must own boot() to wire runtime invariants.',
        );

        $source = File::get(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString('use Carbon\CarbonImmutable;', $source);
        $this->assertStringContainsString('use Illuminate\Database\Eloquent\Model;', $source);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Date;', $source);
    }

    public function test_global_date_resolver_resolves_carbon_immutable(): void
    {
        $resolved = Date::now();

        $this->assertInstanceOf(
            CarbonImmutable::class,
            $resolved,
            'The global Date resolver must resolve to CarbonImmutable so timestamps and now() are immutable.',
        );
    }

    public function test_strict_mode_is_active_in_non_production(): void
    {
        $this->assertFalse(
            $this->app->isProduction(),
            'Guardrail runs outside production; strict mode must be active here.',
        );

        $this->assertTrue(
            Model::preventsLazyLoading()
                && Model::preventsSilentlyDiscardingAttributes()
                && Model::preventsAccessingMissingAttributes(),
            'Eloquent strict mode must be active in non-production environments.',
        );
    }

    #[DataProvider('mutableDateCastTokens')]
    public function test_no_model_declares_a_mutable_date_cast(string $token): void
    {
        $models = File::allFiles(app_path('Models'));

        $this->assertNotEmpty($models, 'Eloquent model directory must exist.');

        foreach ($models as $modelFile) {
            $source = $modelFile->getContents();

            $this->assertStringNotContainsString(
                $token,
                $source,
                sprintf('%s must not declare mutable cast [%s]; use immutable_datetime.', $modelFile->getRelativePathname(), $token),
            );
        }
    }

    /**
     * @return list<array{string}>
     */
    public static function mutableDateCastTokens(): array
    {
        return [
            ["'datetime'"],
            ["'timestamp'"],
        ];
    }
}
