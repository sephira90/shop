<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class AdminCatalogService
{
    /**
     * Create product.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createProduct(array $payload): Product
    {
        $payload['slug'] = $payload['slug'] ?? Str::slug((string) $payload['name']);

        $product = Product::query()->create($payload);

        $this->bumpCatalogVersion();

        return $product->fresh(['category', 'variants.inventory']);
    }

    /**
     * Update product.
     *
     * @param  array<string, mixed>  $payload
     */
    public function updateProduct(Product $product, array $payload): Product
    {
        $payload['slug'] = $payload['slug'] ?? $product->slug;
        $product->update($payload);

        $this->bumpCatalogVersion();

        return $product->fresh(['category', 'variants.inventory']);
    }

    /**
     * Delete product and invalidate catalog cache.
     */
    public function deleteProduct(Product $product): void
    {
        $product->delete();
        $this->bumpCatalogVersion();
    }

    /**
     * Bump cache version to invalidate catalog caches.
     */
    private function bumpCatalogVersion(): void
    {
        $currentVersion = (int) Cache::get('catalog:version', 1);
        Cache::forever('catalog:version', $currentVersion + 1);
    }
}
