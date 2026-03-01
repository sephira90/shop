<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Admin\Products\Dto\CreateAdminProductInputDto;
use App\Http\Requests\Concerns\NormalizesBooleanQueryInput;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    use NormalizesBooleanQueryInput;

    /**
     * Determine if user can perform this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', 'unique:products,slug'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,archived'],
            'is_featured' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:120'],
            'weight_grams' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'variants' => ['nullable', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:64', 'distinct'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:180'],
            'variants.*.attributes' => ['nullable', 'array'],
            'variants.*.price' => ['required_with:variants', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'variants.*.currency' => ['required_with:variants', 'string', 'size:3'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.inventory' => ['nullable', 'array'],
            'variants.*.inventory.quantity' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'variants.*.inventory.reserved_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'variants.*.inventory.low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanInputFields([
            'is_featured',
            'variants.*.is_active',
        ]);
    }

    /**
     * Build typed DTO for create flow.
     */
    public function toDto(): CreateAdminProductInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return CreateAdminProductInputDto::fromValidated($validated);
    }
}
