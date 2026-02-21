import type { ListResponse } from '@/api/response';

export type PromotionType = 'percent' | 'fixed';
export type PromotionStatusFilter = 'all' | 'active' | 'inactive';

export interface Coupon {
    id: number;
    code: string;
    is_active: boolean;
    max_redemptions: number | null;
    redeemed_count: number;
    expires_at: string | null;
}

export interface Promotion {
    id: number;
    name: string;
    code: string | null;
    type: PromotionType;
    value: number;
    is_active: boolean;
    usage_limit: number | null;
    usage_count: number;
    starts_at: string | null;
    ends_at: string | null;
    coupons: Coupon[];
}

export type PromotionListResponse = ListResponse<Promotion>;

export interface PromotionListParams {
    page?: number;
    per_page?: number;
    q?: string;
    is_active?: boolean;
}

export interface PromotionMutationPayload {
    name: string;
    code: string | null;
    type: PromotionType;
    value: number;
    is_active: boolean;
    starts_at: string | null;
    ends_at: string | null;
    usage_limit: number | null;
    coupon?: {
        is_active: boolean;
        max_redemptions: number | null;
        expires_at: string | null;
    };
}

export interface CouponCreatePayload {
    code: string;
    is_active: boolean;
    max_redemptions: number | null;
    expires_at: string | null;
}

export interface CouponUpdatePayload {
    is_active?: boolean;
    max_redemptions?: number | null;
    expires_at?: string | null;
}

export interface PromotionFormState {
    name: string;
    code: string;
    type: PromotionType;
    value: number;
    is_active: boolean;
    starts_at: string;
    ends_at: string;
    usage_limit: string;
    coupon_is_active: boolean;
    coupon_max_redemptions: string;
    coupon_expires_at: string;
}

export interface CouponFormState {
    code: string;
    is_active: boolean;
    max_redemptions: string;
    expires_at: string;
}

export interface PromotionNotice {
    type: 'success' | 'error';
    message: string;
}
