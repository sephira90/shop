<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Admin\Promotions\Dto\CreateAdminPromotionInputDto;
use App\Http\Requests\Concerns\NormalizesBooleanQueryInput;
use App\Models\Promotion;
use App\Support\Data\TypedValue;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class PromotionStoreRequest extends FormRequest
{
    use NormalizesBooleanQueryInput;

    /**
     * Determine if user can perform this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Promotion::class) ?? false;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:140'],
            'code' => ['nullable', 'string', 'max:40', 'unique:promotions,code', 'unique:coupons,code'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => [
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->input('type') === 'percent' && TypedValue::float($value) > 100.0) {
                        $fail('Percent value must be less or equal to 100.');
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'coupon' => ['nullable', 'array'],
            'coupon.code' => ['nullable', 'string', 'max:40', 'unique:coupons,code', 'unique:promotions,code'],
            'coupon.is_active' => ['nullable', 'boolean'],
            'coupon.max_redemptions' => ['nullable', 'integer', 'min:1'],
            'coupon.expires_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanInputFields([
            'is_active',
            'coupon.is_active',
        ]);
    }

    /**
     * Build typed DTO for promotion create flow.
     */
    public function toDto(): CreateAdminPromotionInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return CreateAdminPromotionInputDto::fromValidated($validated);
    }
}
