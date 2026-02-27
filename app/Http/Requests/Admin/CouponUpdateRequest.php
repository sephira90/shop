<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Admin\Promotions\Dto\UpdateAdminPromotionCouponInputDto;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;

class CouponUpdateRequest extends FormRequest
{
    /**
     * Determine if user can perform this request.
     */
    public function authorize(): bool
    {
        $coupon = $this->route('coupon');

        return $coupon instanceof Coupon
            && ($this->user()?->can('update', $coupon) ?? false);
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['nullable', 'boolean'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Build typed DTO for coupon update flow.
     */
    public function toDto(): UpdateAdminPromotionCouponInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return UpdateAdminPromotionCouponInputDto::fromValidated($validated);
    }
}
