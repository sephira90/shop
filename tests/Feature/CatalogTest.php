<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogVariant;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use CreatesCatalogVariant;
    use RefreshDatabase;

    /**
     * Ensure catalog listing returns products.
     */
    public function test_catalog_products_endpoint(): void
    {
        $this->createActiveVariantWithInventory();

        $response = $this->getJson('/api/v1/catalog/products');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data'));
    }

    /**
     * Ensure catalog listing exposes HTTP cache headers.
     */
    public function test_catalog_products_endpoint_has_public_cache_headers(): void
    {
        $this->createActiveVariantWithInventory();

        $response = $this->getJson('/api/v1/catalog/products');

        $response->assertOk()->assertHeader('Cache-Control');
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=60', (string) $response->headers->get('Cache-Control'));
    }

    /**
     * Ensure catalog list rejects invalid filter payload through FormRequest validation.
     */
    public function test_catalog_products_endpoint_rejects_invalid_filters(): void
    {
        $this->createActiveVariantWithInventory();

        $this->getJson('/api/v1/catalog/products?sort=invalid&per_page=0')
            ->assertUnprocessable()
            ->assertJsonPath('error.type', 'ValidationException')
            ->assertJsonStructure([
                'error' => [
                    'message',
                    'type',
                    'validation' => [
                        'sort',
                        'per_page',
                    ],
                ],
            ]);
    }

    /**
     * Ensure catalog list/show use aligned projection shape.
     */
    public function test_catalog_list_and_show_have_consistent_projection_shape(): void
    {
        $this->createActiveVariantWithInventory();

        $listResponse = $this->getJson('/api/v1/catalog/products')
            ->assertOk();

        $listProduct = $this->jsonArray($listResponse, 'data.0');
        $this->assertNotEmpty($listProduct['slug']);

        $showResponse = $this->getJson('/api/v1/catalog/products/'.\App\Support\Data\TypedValue::string($listProduct['slug']))
            ->assertOk();

        $showProduct = $this->jsonArray($showResponse, 'data');

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
     * Ensure catalog price sorting is numeric rather than lexical.
     */
    public function test_catalog_price_sort_orders_products_numerically(): void
    {
        $first = $this->createCatalogProductWithVariants('price-1', [1.00]);
        $second = $this->createCatalogProductWithVariants('price-10', [10.00]);
        $third = $this->createCatalogProductWithVariants('price-100', [100.00]);
        $fourth = $this->createCatalogProductWithVariants('price-2', [2.00]);
        $fifth = $this->createCatalogProductWithVariants('price-20', [20.00]);

        $this->getJson('/api/v1/catalog/products?sort=price_asc')
            ->assertOk()
            ->assertJsonPath('data.0.slug', $first->slug)
            ->assertJsonPath('data.1.slug', $fourth->slug)
            ->assertJsonPath('data.2.slug', $second->slug)
            ->assertJsonPath('data.3.slug', $fifth->slug)
            ->assertJsonPath('data.4.slug', $third->slug);

        $this->getJson('/api/v1/catalog/products?sort=price_desc')
            ->assertOk()
            ->assertJsonPath('data.0.slug', $third->slug)
            ->assertJsonPath('data.1.slug', $fifth->slug)
            ->assertJsonPath('data.2.slug', $second->slug)
            ->assertJsonPath('data.3.slug', $fourth->slug)
            ->assertJsonPath('data.4.slug', $first->slug);
    }

    /**
     * Ensure catalog variants are ordered by their numeric price so list cards expose the cheapest offer first.
     */
    public function test_catalog_list_orders_variants_by_numeric_price(): void
    {
        $product = $this->createCatalogProductWithVariants('bundle-phone', [100.00, 2.00, 10.00]);

        $response = $this->getJson('/api/v1/catalog/products?sort=price_asc')
            ->assertOk();

        /** @var list<array{slug:string,variants:list<array{price:int|float}>}> $payload */
        $payload = $response->json('data');
        $matchingProduct = null;

        foreach ($payload as $item) {
            if ($item['slug'] !== $product->slug) {
                continue;
            }

            $matchingProduct = $item;

            break;
        }

        $this->assertIsArray($matchingProduct);
        $this->assertEquals(2.0, $matchingProduct['variants'][0]['price']);
        $this->assertEquals(10.0, $matchingProduct['variants'][1]['price']);
        $this->assertEquals(100.0, $matchingProduct['variants'][2]['price']);
    }

    /**
     * @param  list<float>  $prices
     */
    private function createCatalogProductWithVariants(string $slug, array $prices): Product
    {
        $product = Product::query()->create([
            'sku' => strtoupper($slug),
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'status' => 'active',
            'published_at' => now(),
        ]);

        foreach ($prices as $index => $price) {
            ProductVariant::query()->create([
                'product_id' => $product->id,
                'sku' => strtoupper($slug).'-'.($index + 1),
                'name' => 'Variant '.($index + 1),
                'attributes' => ['position' => $index + 1],
                'price' => $price,
                'compare_at_price' => null,
                'currency' => 'USD',
                'is_active' => true,
            ]);
        }

        return $product;
    }
}
