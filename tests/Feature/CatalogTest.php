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
     * Ensure catalog list/show use aligned projection shape.
     */
    public function test_catalog_list_and_show_have_consistent_projection_shape(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $listResponse = $this->getJson('/api/v1/catalog/products')
            ->assertOk();

        $listProduct = $listResponse->json('data.0');
        $this->assertIsArray($listProduct);
        $this->assertNotEmpty($listProduct['slug']);

        $showResponse = $this->getJson('/api/v1/catalog/products/'.$listProduct['slug'])
            ->assertOk();

        $showProduct = $showResponse->json('data');
        $this->assertIsArray($showProduct);

        $expectedKeys = [
            'id',
            'sku',
            'name',
            'slug',
            'short_description',
            'description',
            'status',
            'is_featured',
            'brand',
            'weight_grams',
            'category',
            'meta',
            'variants',
            'published_at',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $listProduct);
            $this->assertArrayHasKey($key, $showProduct);
        }
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
