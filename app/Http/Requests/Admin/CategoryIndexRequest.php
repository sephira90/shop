<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Admin\Categories\Dto\AdminCategoryListFilterDto;
use App\Http\Requests\Concerns\NormalizesBooleanQueryInput;
use Illuminate\Foundation\Http\FormRequest;

class CategoryIndexRequest extends FormRequest
{
    use NormalizesBooleanQueryInput;

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
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanInputFields(['is_active']);
    }

    /**
     * Build typed filter object for category list query.
     */
    public function filter(): AdminCategoryListFilterDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return AdminCategoryListFilterDto::fromValidated($validated);
    }
}
