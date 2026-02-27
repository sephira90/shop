import type { PromotionCouponWireDto, PromotionWireDto } from "@/contracts/api/v1/admin-promotions";
import type {
    Coupon,
    CouponCreatePayload,
    CouponUpdatePayload,
    Promotion,
    PromotionMutationPayload,
    PromotionType,
} from "@/types/admin-promotions";

const mapPromotionType = (value: PromotionWireDto["type"]): PromotionType => {
    return value === "fixed" ? "fixed" : "percent";
};

export const mapCouponFromApi = (value: PromotionCouponWireDto): Coupon => {
    return {
        id: value.id,
        code: value.code,
        is_active: value.is_active,
        max_redemptions: value.max_redemptions,
        redeemed_count: value.redeemed_count,
        expires_at: value.expires_at,
    };
};

export const mapPromotionFromApi = (value: PromotionWireDto): Promotion => {
    return {
        id: value.id,
        name: value.name,
        code: value.code,
        type: mapPromotionType(value.type),
        value: value.value,
        is_active: value.is_active,
        usage_limit: value.usage_limit,
        usage_count: value.usage_count,
        starts_at: value.starts_at,
        ends_at: value.ends_at,
        coupons: value.coupons.map((coupon) => mapCouponFromApi(coupon)),
    };
};

export const mapPromotionListFromApi = (value: PromotionWireDto[]): Promotion[] => {
    return value.map((item) => mapPromotionFromApi(item));
};

export const toPromotionMutationDto = (
    payload: PromotionMutationPayload,
): PromotionMutationPayload => {
    return {
        name: payload.name.trim(),
        code: payload.code,
        type: payload.type,
        value: payload.value,
        is_active: payload.is_active,
        starts_at: payload.starts_at,
        ends_at: payload.ends_at,
        usage_limit: payload.usage_limit,
        coupon: payload.coupon
            ? {
                  is_active: payload.coupon.is_active,
                  max_redemptions: payload.coupon.max_redemptions,
                  expires_at: payload.coupon.expires_at,
              }
            : undefined,
    };
};

export const toCouponCreateDto = (payload: CouponCreatePayload): CouponCreatePayload => {
    return {
        code: payload.code.trim(),
        is_active: payload.is_active,
        max_redemptions: payload.max_redemptions,
        expires_at: payload.expires_at,
    };
};

export const toCouponUpdateDto = (payload: CouponUpdatePayload): CouponUpdatePayload => {
    const dto: CouponUpdatePayload = {};

    if (Object.hasOwn(payload, "is_active")) {
        dto.is_active = payload.is_active;
    }
    if (Object.hasOwn(payload, "max_redemptions")) {
        dto.max_redemptions = payload.max_redemptions;
    }
    if (Object.hasOwn(payload, "expires_at")) {
        dto.expires_at = payload.expires_at;
    }

    return dto;
};
