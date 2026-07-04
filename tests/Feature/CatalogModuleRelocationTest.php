<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Catalog\Contracts\CatalogCacheVersion;
use App\Domains\Catalog\Contracts\CatalogProductReadRepository;
use App\Domains\Catalog\Contracts\CatalogReadService;
use App\Domains\Catalog\Repositories\CatalogProductReadRepository as CatalogProductReadRepositoryImplementation;
use App\Domains\Catalog\Services\CatalogService;
use App\Domains\Catalog\Services\CatalogVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogVariant;
use Tests\TestCase;

/**
 * C1 relocation smoke: verifies the Catalog slice continues to resolve and
 * respond identically after the namespace move into app/Domains/Catalog/.
 *
 * The detailed wire-contract verification stays in OpenApiConformanceFeatureTest;
 * this test specifically locks the module wiring (provider bindings, contract
 * surfaces, controller namespace) so a regression in the relocation is caught
 * with a focused failure message.
 */
class CatalogModuleRelocationTest extends TestCase
{
    use CreatesCatalogVariant;
    use RefreshDatabase;

    public function test_catalog_module_contracts_are_bound_to_module_implementations(): void
    {
        $this->assertInstanceOf(
            CatalogVersionService::class,
            $this->app->make(CatalogCacheVersion::class),
            'CatalogCacheVersion contract must resolve to the module implementation.',
        );

        $this->assertInstanceOf(
            CatalogService::class,
            $this->app->make(CatalogReadService::class),
            'CatalogReadService contract must resolve to the module implementation.',
        );

        $this->assertInstanceOf(
            CatalogProductReadRepositoryImplementation::class,
            $this->app->make(CatalogProductReadRepository::class),
            'CatalogProductReadRepository contract must resolve to the module implementation.',
        );
    }

    public function test_catalog_controller_resolves_from_module_namespace(): void
    {
        $this->createActiveVariantWithInventory();

        // The route resolves to App\Domains\Catalog\Controllers\CatalogController.
        // A 200 response here proves the full wiring chain survived the move:
        // route definition → controller → handlers → CatalogReadService →
        // CatalogProductReadRepository → Eloquent.
        $this->getJson('/api/v1/catalog/products')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $controller = $routes->getByAction('App\\Domains\\Catalog\\Controllers\\CatalogController@index');

        $this->assertNotNull(
            $controller,
            'CatalogController must be registered under the App\\Domains\\Catalog\\Controllers namespace.',
        );
    }
}
