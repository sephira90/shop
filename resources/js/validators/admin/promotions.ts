import type {
    CouponCreatePayload,
    CouponFormState,
    PromotionFormState,
    PromotionMutationPayload,
} from "@/types/admin-promotions";

const parseNullableInt = (value: string): number | null => {
    const normalized = value.trim();
    if (normalized === "") {
        return null;
    }

    const parsed = Number.parseInt(normalized, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
};

export const normalizeCouponCode = (value: string): string => value.trim().toUpperCase();

export const createPromotionFormState = (): PromotionFormState => ({
    name: "",
    code: "",
    type: "percent",
    value: 10,
    is_active: true,
    starts_at: "",
    ends_at: "",
    usage_limit: "",
    coupon_is_active: true,
    coupon_max_redemptions: "",
    coupon_expires_at: "",
});

export const createCouponFormState = (): CouponFormState => ({
    code: "",
    is_active: true,
    max_redemptions: "",
    expires_at: "",
});

export const buildPromotionMutationPayload = (
    form: PromotionFormState,
    isEditing: boolean,
): PromotionMutationPayload => {
    const normalizedCode = normalizeCouponCode(form.code);
    const payload: PromotionMutationPayload = {
        name: form.name.trim(),
        code: normalizedCode === "" ? null : normalizedCode,
        type: form.type,
        value: Number(form.value),
        is_active: form.is_active,
        starts_at: form.starts_at.trim() === "" ? null : form.starts_at,
        ends_at: form.ends_at.trim() === "" ? null : form.ends_at,
        usage_limit: parseNullableInt(form.usage_limit),
    };

    if (!isEditing && normalizedCode !== "") {
        payload.coupon = {
            is_active: form.coupon_is_active,
            max_redemptions: parseNullableInt(form.coupon_max_redemptions),
            expires_at: form.coupon_expires_at.trim() === "" ? null : form.coupon_expires_at,
        };
    }

    return payload;
};

export const buildCouponCreatePayload = (form: CouponFormState): CouponCreatePayload => ({
    code: normalizeCouponCode(form.code),
    is_active: form.is_active,
    max_redemptions: parseNullableInt(form.max_redemptions),
    expires_at: form.expires_at.trim() === "" ? null : form.expires_at,
});
