<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure catalog listing returns products.
     */
    public function test_catalog_products_endpoint(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $response = $this->getJson('/api/v1/catalog/products');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data'));
    }

    /**
     * Ensure catalog listing exposes HTTP cache headers.
     */
    public function test_catalog_products_endpoint_has_public_cache_headers(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $response = $this->getJson('/api/v1/catalog/products');

        $response->assertOk()->assertHeader('Cache-Control');
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=60', (string) $response->headers->get('Cache-Control'));
    }

    /**
     * Ensure SPA shell route is cacheable and does not start session.
     */
    public function test_spa_shell_route_is_publicly_cacheable_without_session_cookie(): void
    {
        $this->withoutVite();

        $response = $this->get('/');
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $response->assertOk()->assertHeader('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=300', $cacheControl);
        $this->assertFalse($response->headers->has('set-cookie'));
    }
}
