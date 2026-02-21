<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Inventory;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\CatalogVersionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AdminCatalogService
{
    /**
     * Create service instance.
     */
    public function __construct(
        private readonly CatalogVersionService $catalogVersionService,
    ) {}

    /**
     * Create product.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createProduct(array $payload): Product
    {
        return DB::transaction(function () use ($payload): Product {
            $variants = $this->extractVariantsPayload($payload);
            $payload['slug'] = $payload['slug'] ?? Str::slug((string) $payload['name']);

            $product = Product::query()->create($payload);

            if ($variants !== null) {
                $this->syncVariants($product, $variants);
            }

            $this->catalogVersionService->bump();

            return $product->fresh(['category', 'variants.inventory']);
        });
    }

    /**
     * Update product.
     *
     * @param  array<string, mixed>  $payload
     */
    public function updateProduct(Product $product, array $payload): Product
    {
        return DB::transaction(function () use ($product, $payload): Product {
            $variants = $this->extractVariantsPayload($payload);
            $payload['slug'] = $payload['slug'] ?? $product->slug;
            $product->update($payload);

            if ($variants !== null) {
                $this->syncVariants($product, $variants);
            }

            $this->catalogVersionService->bump();

            return $product->fresh(['category', 'variants.inventory']);
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

    /**
     * Extract variants payload from product payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>|null
     */
    private function extractVariantsPayload(array &$payload): ?array
    {
        if (! array_key_exists('variants', $payload)) {
            return null;
        }

        $variants = $payload['variants'];
        unset($payload['variants']);

        return is_array($variants) ? $variants : null;
    }

    /**
     * Sync full variant set for a product.
     *
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $keptVariantIds = [];

        foreach ($variants as $index => $variantPayload) {
            $variant = $this->resolveVariant($product, $variantPayload, $index);
            $preparedPayload = $this->prepareVariantPayload($variantPayload);

            if ($variant instanceof ProductVariant) {
                $variant->update($preparedPayload);
            } else {
                /** @var ProductVariant $variant */
                $variant = $product->variants()->create($preparedPayload);
            }

            $keptVariantIds[] = $variant->id;
            $this->syncVariantInventory($variant, $variantPayload);
            $this->syncVariantPrice($variant);
        }

        if ($keptVariantIds === []) {
            return;
        }

        $staleVariants = $product->variants()
            ->whereNotIn('id', $keptVariantIds)
            ->get();

        foreach ($staleVariants as $staleVariant) {
            if (! $staleVariant instanceof ProductVariant) {
                continue;
            }

            $staleVariant->delete();
        }
    }

    /**
     * Resolve variant by id/sku and guard against cross-product collisions.
     *
     * @param  array<string, mixed>  $variantPayload
     */
    private function resolveVariant(Product $product, array $variantPayload, int $index): ?ProductVariant
    {
        $variantId = isset($variantPayload['id']) ? (int) $variantPayload['id'] : 0;

        if ($variantId > 0) {
            $variant = $product->variants()->whereKey($variantId)->first();

            if (! $variant instanceof ProductVariant) {
                throw ValidationException::withMessages([
                    "variants.$index.id" => ['Variant id does not belong to this product.'],
                ]);
            }

            $nextSku = trim((string) ($variantPayload['sku'] ?? ''));

            if ($nextSku !== '' && $nextSku !== $variant->sku) {
                $skuCollision = ProductVariant::query()
                    ->where('sku', $nextSku)
                    ->where('id', '!=', $variant->id)
                    ->first();

                if ($skuCollision instanceof ProductVariant) {
                    throw ValidationException::withMessages([
                        "variants.$index.sku" => ['Variant SKU already exists.'],
                    ]);
                }
            }

            return $variant;
        }

        $sku = trim((string) ($variantPayload['sku'] ?? ''));

        if ($sku === '') {
            return null;
        }

        $ownVariant = $product->variants()->where('sku', $sku)->first();

        if ($ownVariant instanceof ProductVariant) {
            return $ownVariant;
        }

        $existingVariant = ProductVariant::query()->where('sku', $sku)->first();

        if ($existingVariant instanceof ProductVariant) {
            throw ValidationException::withMessages([
                "variants.$index.sku" => ['Variant SKU already exists.'],
            ]);
        }

        return null;
    }

    /**
     * Normalize variant payload before persist.
     *
     * @param  array<string, mixed>  $variantPayload
     * @return array<string, mixed>
     */
    private function prepareVariantPayload(array $variantPayload): array
    {
        $compareAtPrice = $variantPayload['compare_at_price'] ?? null;
        $compareAtPrice = $compareAtPrice === null || $compareAtPrice === '' ? null : (float) $compareAtPrice;

        $attributes = $variantPayload['attributes'] ?? [];
        $attributes = is_array($attributes) ? $attributes : [];

        return [
            'sku' => trim((string) $variantPayload['sku']),
            'name' => trim((string) $variantPayload['name']),
            'attributes' => $attributes,
            'price' => (float) $variantPayload['price'],
            'compare_at_price' => $compareAtPrice,
            'currency' => strtoupper((string) ($variantPayload['currency'] ?? 'USD')),
            'is_active' => (bool) ($variantPayload['is_active'] ?? true),
        ];
    }

    /**
     * Persist variant inventory state.
     *
     * @param  array<string, mixed>  $variantPayload
     */
    private function syncVariantInventory(ProductVariant $variant, array $variantPayload): void
    {
        $inventoryPayload = $variantPayload['inventory'] ?? [];
        $inventoryPayload = is_array($inventoryPayload) ? $inventoryPayload : [];

        $quantity = max(0, (int) ($inventoryPayload['quantity'] ?? 0));
        $reservedQuantity = max(0, (int) ($inventoryPayload['reserved_quantity'] ?? 0));
        $lowStockThreshold = max(0, (int) ($inventoryPayload['low_stock_threshold'] ?? 3));

        if ($reservedQuantity > $quantity) {
            $reservedQuantity = $quantity;
        }

        Inventory::query()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            [
                'quantity' => $quantity,
                'reserved_quantity' => $reservedQuantity,
                'low_stock_threshold' => $lowStockThreshold,
            ]
        );
    }

    /**
     * Sync current variant price to prices table.
     */
    private function syncVariantPrice(ProductVariant $variant): void
    {
        Price::query()->updateOrCreate(
            [
                'product_variant_id' => $variant->id,
                'currency' => $variant->currency,
                'starts_at' => null,
                'ends_at' => null,
            ],
            [
                'amount' => $variant->price,
            ]
        );
    }
}
