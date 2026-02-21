<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Promotion;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionUpdateRequest extends FormRequest
{
    /**
     * Determine if user can perform this request.
     */
    public function authorize(): bool
    {
        $promotion = $this->route('promotion');

        return $promotion instanceof Promotion
            && ($this->user()?->can('update', $promotion) ?? false);
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $promotionId = $this->route('promotion')?->id;

        return [
            'name' => ['required', 'string', 'max:140'],
            'code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('promotions', 'code')->ignore($promotionId),
                Rule::unique('coupons', 'code')->where(static fn ($query) => $query->where('promotion_id', '!=', $promotionId)),
            ],
            'type' => ['required', 'in:percent,fixed'],
            'value' => [
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->input('type') === 'percent' && (float) $value > 100.0) {
                        $fail('Percent value must be less or equal to 100.');
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
