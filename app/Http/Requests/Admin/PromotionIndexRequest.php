<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Admin\Promotions\Dto\AdminPromotionListFilterDto;
use Illuminate\Foundation\Http\FormRequest;

class PromotionIndexRequest extends FormRequest
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
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Build typed filter object for promotion list query.
     */
    public function filter(): AdminPromotionListFilterDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return AdminPromotionListFilterDto::fromValidated($validated);
    }
}
