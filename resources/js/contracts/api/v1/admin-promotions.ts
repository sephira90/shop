export type PromotionTypeWireDto = "percent" | "fixed";

export interface PromotionCouponWireDto {
    id: number;
    code: string;
    is_active: boolean;
    max_redemptions: number | null;
    redeemed_count: number;
    expires_at: string | null;
}

export interface PromotionWireDto {
    id: number;
    name: string;
    code: string | null;
    type: PromotionTypeWireDto;
    value: number;
    is_active: boolean;
    usage_limit: number | null;
    usage_count: number;
    starts_at: string | null;
    ends_at: string | null;
    coupons: PromotionCouponWireDto[];
    created_at?: string | null;
    updated_at?: string | null;
}
