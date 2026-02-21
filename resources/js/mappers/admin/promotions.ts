import type {
    Coupon,
    CouponCreatePayload,
    CouponUpdatePayload,
    Promotion,
    PromotionMutationPayload,
    PromotionType,
} from '@/types/admin-promotions';

import { asArray, asRecord, toBoolean, toInteger, toNullableInteger, toNullableString, toNumber, toString } from '@/mappers/common';

const mapPromotionType = (value: unknown): PromotionType => {
    return toString(value).toLowerCase() === 'fixed' ? 'fixed' : 'percent';
};

export const mapCouponFromApi = (value: unknown): Coupon => {
    const record = asRecord(value);

    return {
        id: toInteger(record.id),
        code: toString(record.code),
        is_active: toBoolean(record.is_active, true),
        max_redemptions: toNullableInteger(record.max_redemptions),
        redeemed_count: toInteger(record.redeemed_count),
        expires_at: toNullableString(record.expires_at),
    };
};

export const mapPromotionFromApi = (value: unknown): Promotion => {
    const record = asRecord(value);

    return {
        id: toInteger(record.id),
        name: toString(record.name),
        code: toNullableString(record.code),
        type: mapPromotionType(record.type),
        value: toNumber(record.value),
        is_active: toBoolean(record.is_active, true),
        usage_limit: toNullableInteger(record.usage_limit),
        usage_count: toInteger(record.usage_count),
        starts_at: toNullableString(record.starts_at),
        ends_at: toNullableString(record.ends_at),
        coupons: asArray(record.coupons).map((coupon) => mapCouponFromApi(coupon)),
    };
};

export const mapPromotionListFromApi = (value: unknown): Promotion[] => {
    return asArray(value).map((item) => mapPromotionFromApi(item));
};

export const toPromotionMutationDto = (payload: PromotionMutationPayload): PromotionMutationPayload => {
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

    if (Object.hasOwn(payload, 'is_active')) {
        dto.is_active = payload.is_active;
    }
    if (Object.hasOwn(payload, 'max_redemptions')) {
        dto.max_redemptions = payload.max_redemptions;
    }
    if (Object.hasOwn(payload, 'expires_at')) {
        dto.expires_at = payload.expires_at;
    }

    return dto;
};
