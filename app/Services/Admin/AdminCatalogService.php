<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Application\Admin\Products\Dto\CreateAdminProductInputDto;
use App\Application\Admin\Products\Dto\UpdateAdminProductInputDto;
use App\Models\Product;
use App\Services\Admin\ProductWrites\AdminProductVariantSyncService;
use App\Services\Catalog\CatalogVersionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AdminCatalogService
{
    /**
     * Create service instance.
     */
    public function __construct(
        private readonly CatalogVersionService $catalogVersionService,
        private readonly AdminProductVariantSyncService $adminProductVariantSyncService,
    ) {}

    /**
     * Create product.
     */
    public function createProduct(CreateAdminProductInputDto $input): Product
    {
        return DB::transaction(function () use ($input): Product {
            $attributes = $input->toPersistenceAttributes();
            $attributes['slug'] = $attributes['slug'] ?? Str::slug($input->name);

            $product = Product::query()->create($attributes);

            if ($input->variants !== null) {
                $this->adminProductVariantSyncService->sync($product, $input->variants);
            }

            $this->catalogVersionService->bump();

            return $product->refresh()->load(['category', 'variants.inventory']);
        });
    }

    /**
     * Update product.
     */
    public function updateProduct(Product $product, UpdateAdminProductInputDto $input): Product
    {
        return DB::transaction(function () use ($product, $input): Product {
            $attributes = $input->toPersistenceAttributes();
            $attributes['slug'] = $attributes['slug'] ?? $product->slug;
            $product->update($attributes);

            if ($input->variants !== null) {
                $this->adminProductVariantSyncService->sync($product, $input->variants);
            }

            $this->catalogVersionService->bump();

            return $product->refresh()->load(['category', 'variants.inventory']);
        });
    }

    /**
     * Delete product and invalidate catalog cache.
     */
    public function deleteProduct(Product $product): void
    {
        $product->delete();
        $this->catalogVersionService->bump();
    }
}
