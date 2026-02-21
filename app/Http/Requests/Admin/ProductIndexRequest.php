<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Filters\Admin\AdminProductListFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProductIndexRequest extends FormRequest
{
    /**
     * Determine if user can perform this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', new Enum(ProductStatus::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    /**
     * Build typed filter object for product list query.
     */
    public function filter(): AdminProductListFilter
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return AdminProductListFilter::fromValidated($validated);
    }
}
