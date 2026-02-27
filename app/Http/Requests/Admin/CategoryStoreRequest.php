<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Admin\Categories\Dto\CreateAdminCategoryInputDto;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class CategoryStoreRequest extends FormRequest
{
    /**
     * Determine if user can perform this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Category::class) ?? false;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * Build typed DTO for create flow.
     */
    public function toDto(): CreateAdminCategoryInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return CreateAdminCategoryInputDto::fromValidated($validated);
    }
}
