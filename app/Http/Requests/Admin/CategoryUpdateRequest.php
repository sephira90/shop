<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Admin\Categories\Dto\UpdateAdminCategoryInputDto;
use App\Http\Requests\Concerns\NormalizesBooleanQueryInput;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryUpdateRequest extends FormRequest
{
    use NormalizesBooleanQueryInput;

    /**
     * Determine if user can perform this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category
            && ($this->user()?->can('update', $category) ?? false);
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $categoryId = $this->routeCategory()?->id;

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                Rule::notIn([$categoryId]),
            ],
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanInputFields(['is_active']);
    }

    /**
     * Build typed DTO for update flow.
     */
    public function toDto(): UpdateAdminCategoryInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();
        $category = $this->routeCategory();
        $existingSlug = $category instanceof Category ? (string) $category->slug : '';

        return UpdateAdminCategoryInputDto::fromValidated($validated, $existingSlug);
    }

    private function routeCategory(): ?Category
    {
        $category = $this->route('category');

        return $category instanceof Category ? $category : null;
    }
}
