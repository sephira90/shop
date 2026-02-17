<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * Transform resource into array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'status' => $this->status?->value,
            'is_featured' => $this->is_featured,
            'category' => $this->category === null ? null : [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'meta' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
            ],
            'variants' => $this->variants->map(static fn ($variant): array => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'attributes' => $variant->attributes,
                'price' => (float) $variant->price,
                'compare_at_price' => $variant->compare_at_price !== null ? (float) $variant->compare_at_price : null,
                'currency' => $variant->currency,
                'is_active' => $variant->is_active,
                'inventory' => [
                    'quantity' => $variant->inventory?->quantity,
                    'reserved_quantity' => $variant->inventory?->reserved_quantity,
                    'available_quantity' => $variant->inventory?->availableQuantity(),
                ],
            ])->values(),
            'published_at' => $this->published_at?->toAtomString(),
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
