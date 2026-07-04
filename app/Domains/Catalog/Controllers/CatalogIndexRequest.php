<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Controllers;

use App\Domains\Catalog\Contracts\Dto\CatalogProductListFilterDto;
use App\Support\Data\TypedValue;
use Illuminate\Foundation\Http\FormRequest;

final class CatalogIndexRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:120'],
            'category_slug' => ['nullable', 'string', 'max:180'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc,name_asc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }

    /**
     * Build typed filter object for catalog list query.
     */
    public function filter(): CatalogProductListFilterDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return CatalogProductListFilterDto::fromValidated($validated);
    }

    /**
     * Resolve validated page size with transport default.
     */
    public function perPage(): int
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return TypedValue::int($validated['per_page'] ?? 12);
    }
}
