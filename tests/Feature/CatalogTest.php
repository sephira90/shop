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
}
