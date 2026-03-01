<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Admin\Categories\Dto\AdminCategoryOptionListFilterDto;
use Illuminate\Foundation\Http\FormRequest;

class CategoryOptionsRequest extends FormRequest
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
            'exclude_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Build typed filter object for category selector query.
     */
    public function filter(): AdminCategoryOptionListFilterDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return AdminCategoryOptionListFilterDto::fromValidated($validated);
    }
}
