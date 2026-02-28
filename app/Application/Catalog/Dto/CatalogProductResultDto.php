<?php

declare(strict_types=1);

namespace App\Application\Catalog\Dto;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use DateTimeInterface;
use Illuminate\Support\Collection;

final readonly class CatalogProductResultDto
{
    public static function fromProduct(Product $product): self
    {
        $category = null;
        if ($product->relationLoaded('category')) {
            $loadedCategory = $product->getRelation('category');
            if ($loadedCategory instanceof Category) {
                $category = CatalogProductCategoryResultDto::fromCategory($loadedCategory);
            }
        }

        /** @var list<CatalogProductVariantResultDto> $variants */
        $variants = [];
        if ($product->relationLoaded('variants')) {
            $loadedVariants = $product->getRelation('variants');
            if ($loadedVariants instanceof Collection) {
                foreach ($loadedVariants as $variant) {
                    if ($variant instanceof ProductVariant) {
                        $variants[] = CatalogProductVariantResultDto::fromVariant($variant);
                    }
                }
            }
        }

        return new self(
            id: (int) $product->id,
            sku: (string) $product->sku,
            name: (string) $product->name,
            slug: (string) $product->slug,
            shortDescription: self::nullableString($product->short_description),
            description: self::nullableString($product->description),
            status: self::resolveStatus($product),
            isFeatured: (bool) $product->is_featured,
            brand: self::nullableString($product->brand),
            weightGrams: self::nullableInt($product->weight_grams),
            category: $category,
            metaTitle: self::nullableString($product->meta_title),
            metaDescription: self::nullableString($product->meta_description),
            variants: $variants,
            publishedAt: self::formatDateLike($product->published_at),
            createdAt: self::formatDateLike($product->created_at),
            updatedAt: self::formatDateLike($product->updated_at),
        );
    }

    /**
     * @param  list<CatalogProductVariantResultDto>  $variants
     */
    public function __construct(
        public int $id,
        public string $sku,
        public string $name,
        public string $slug,
        public ?string $shortDescription,
        public ?string $description,
        public ?string $status,
        public bool $isFeatured,
        public ?string $brand,
        public ?int $weightGrams,
        public ?CatalogProductCategoryResultDto $category,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public array $variants,
        public ?string $publishedAt,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * @return array{
     *     id:int,
     *     sku:string,
     *     name:string,
     *     slug:string,
     *     short_description:string|null,
     *     description:string|null,
     *     status:string|null,
     *     is_featured:bool,
     *     brand:string|null,
     *     weight_grams:int|null,
     *     category:array{id:int,name:string,slug:string}|null,
     *     meta:array{title:string|null,description:string|null},
     *     variants:list<array{
     *         id:int,
     *         sku:string,
     *         name:string,
     *         attributes:array<string, mixed>|object|null,
     *         price:float,
     *         compare_at_price:float|null,
     *         currency:string,
     *         is_active:bool,
     *         inventory:array{
     *             quantity:int|null,
     *             reserved_quantity:int|null,
     *             available_quantity:int|null
     *         }
     *     }>,
     *     published_at:string|null,
     *     created_at:string|null,
     *     updated_at:string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'status' => $this->status,
            'is_featured' => $this->isFeatured,
            'brand' => $this->brand,
            'weight_grams' => $this->weightGrams,
            'category' => $this->category?->toArray(),
            'meta' => [
                'title' => $this->metaTitle,
                'description' => $this->metaDescription,
            ],
            'variants' => array_map(
                static fn (CatalogProductVariantResultDto $variant): array => $variant->toArray(),
                $this->variants
            ),
            'published_at' => $this->publishedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    private static function resolveStatus(Product $product): ?string
    {
        $status = $product->getRawOriginal('status');

        if (! is_string($status) || trim($status) === '') {
            return null;
        }

        return $status;
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private static function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function formatDateLike(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }
}
