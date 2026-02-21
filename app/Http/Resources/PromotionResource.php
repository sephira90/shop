<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Coupon;
use App\Models\Promotion;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Promotion */
class PromotionResource extends JsonResource
{
    /**
     * Transform resource into array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = $this->resource->getAttribute('type');
        if ($type instanceof BackedEnum) {
            $type = $type->value;
        }
        $type = is_string($type) ? $type : null;

        /** @var \Illuminate\Database\Eloquent\Collection<int, Coupon> $coupons */
        $coupons = $this->coupons;
        $serializedCoupons = [];
        foreach ($coupons as $coupon) {
            $serializedCoupons[] = [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'is_active' => $coupon->is_active,
                'max_redemptions' => $coupon->max_redemptions,
                'redeemed_count' => $coupon->redeemed_count,
                'expires_at' => $this->toAtomStringOrNull($coupon->expires_at),
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $type,
            'value' => (float) $this->value,
            'is_active' => $this->is_active,
            'usage_limit' => $this->usage_limit,
            'usage_count' => $this->usage_count,
            'starts_at' => $this->toAtomStringOrNull($this->starts_at),
            'ends_at' => $this->toAtomStringOrNull($this->ends_at),
            'coupons' => $serializedCoupons,
            'created_at' => $this->toAtomStringOrNull($this->created_at),
            'updated_at' => $this->toAtomStringOrNull($this->updated_at),
        ];
    }

    /**
     * Convert datetime-like value to atom string if possible.
     */
    private function toAtomStringOrNull(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        return null;
    }
}
