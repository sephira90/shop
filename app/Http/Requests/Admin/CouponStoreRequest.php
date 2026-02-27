<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Admin\Promotions\Dto\CreateAdminPromotionCouponInputDto;
use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;

class CouponStoreRequest extends FormRequest
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
        return [
            'code' => ['required', 'string', 'max:40', 'unique:coupons,code', 'unique:promotions,code'],
            'is_active' => ['nullable', 'boolean'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Build typed DTO for coupon create flow.
     */
    public function toDto(): CreateAdminPromotionCouponInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return CreateAdminPromotionCouponInputDto::fromValidatedWithRequiredCode($validated);
    }
}
