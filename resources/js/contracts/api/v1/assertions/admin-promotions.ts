import { ApiContractError } from "@/api/response";
import type {
    PromotionCouponWireDto,
    PromotionTypeWireDto,
    PromotionWireDto,
} from "@/contracts/api/v1/admin-promotions";

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === "object" && value !== null && !Array.isArray(value);

const requireString = (record: Record<string, unknown>, key: string): string => {
    const value = record[key];

    if (typeof value !== "string") {
        throw new ApiContractError(`Promotion payload field \`${key}\` must be string.`);
    }

    return value;
};

const requireBoolean = (record: Record<string, unknown>, key: string): boolean => {
    const value = record[key];

    if (typeof value !== "boolean") {
        throw new ApiContractError(`Promotion payload field \`${key}\` must be boolean.`);
    }

    return value;
};

const requireNumber = (record: Record<string, unknown>, key: string): number => {
    const value = Number(record[key]);

    if (!Number.isFinite(value)) {
        throw new ApiContractError(`Promotion payload field \`${key}\` must be number.`);
    }

    return value;
};

const parseNullableString = (record: Record<string, unknown>, key: string): string | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    if (typeof value === "string") {
        return value;
    }

    throw new ApiContractError(`Promotion payload field \`${key}\` must be string|null.`);
};

const parseNullableNumber = (record: Record<string, unknown>, key: string): number | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    const numeric = Number(value);
    if (!Number.isFinite(numeric)) {
        throw new ApiContractError(`Promotion payload field \`${key}\` must be number|null.`);
    }

    return numeric;
};

const parsePromotionType = (record: Record<string, unknown>, key: string): PromotionTypeWireDto => {
    const value = record[key];

    if (value === "percent" || value === "fixed") {
        return value;
    }

    throw new ApiContractError(`Promotion payload field \`${key}\` must be 'percent'|'fixed'.`);
};

const parseCoupons = (value: unknown): PromotionCouponWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Promotion payload field `coupons` must be array.");
    }

    return value.map((coupon): PromotionCouponWireDto => {
        if (!isRecord(coupon)) {
            throw new ApiContractError("Promotion coupon payload must be object.");
        }

        return {
            id: requireNumber(coupon, "id"),
            code: requireString(coupon, "code"),
            is_active: requireBoolean(coupon, "is_active"),
            max_redemptions: parseNullableNumber(coupon, "max_redemptions"),
            redeemed_count: requireNumber(coupon, "redeemed_count"),
            expires_at: parseNullableString(coupon, "expires_at"),
        };
    });
};

export const assertPromotionWireDto = (value: unknown): PromotionWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Promotion payload must be an object.");
    }

    return {
        id: requireNumber(value, "id"),
        name: requireString(value, "name"),
        code: parseNullableString(value, "code"),
        type: parsePromotionType(value, "type"),
        value: requireNumber(value, "value"),
        is_active: requireBoolean(value, "is_active"),
        usage_limit: parseNullableNumber(value, "usage_limit"),
        usage_count: requireNumber(value, "usage_count"),
        starts_at: parseNullableString(value, "starts_at"),
        ends_at: parseNullableString(value, "ends_at"),
        coupons: parseCoupons(value.coupons),
        created_at: Object.hasOwn(value, "created_at")
            ? parseNullableString(value, "created_at")
            : undefined,
        updated_at: Object.hasOwn(value, "updated_at")
            ? parseNullableString(value, "updated_at")
            : undefined,
    };
};

export const assertPromotionCouponWireDto = (value: unknown): PromotionCouponWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Promotion coupon payload must be an object.");
    }

    return {
        id: requireNumber(value, "id"),
        code: requireString(value, "code"),
        is_active: requireBoolean(value, "is_active"),
        max_redemptions: parseNullableNumber(value, "max_redemptions"),
        redeemed_count: requireNumber(value, "redeemed_count"),
        expires_at: parseNullableString(value, "expires_at"),
    };
};
