import { describe, expect, it } from "vitest";

import {
    buildCouponCreatePayload,
    buildPromotionMutationPayload,
    createCouponFormState,
    createPromotionFormState,
    normalizeCouponCode,
} from "@/validators/admin/promotions";

describe("promotion validator", () => {
    it("normalizes coupon code to uppercase", () => {
        expect(normalizeCouponCode("  test 1  ")).toBe("TEST 1");
    });

    it("creates default states", () => {
        expect(createPromotionFormState().type).toBe("percent");
        expect(createCouponFormState().is_active).toBe(true);
    });

    it("builds promotion payload with coupon for create mode", () => {
        const payload = buildPromotionMutationPayload(
            {
                name: "  Spring Sale  ",
                code: "  spring10  ",
                type: "percent",
                value: 10,
                is_active: true,
                starts_at: "",
                ends_at: "2026-03-10T10:00:00Z",
                usage_limit: "100",
                coupon_is_active: false,
                coupon_max_redemptions: "50",
                coupon_expires_at: "2026-03-15T10:00:00Z",
            },
            false,
        );

        expect(payload).toEqual({
            name: "Spring Sale",
            code: "SPRING10",
            type: "percent",
            value: 10,
            is_active: true,
            starts_at: null,
            ends_at: "2026-03-10T10:00:00Z",
            usage_limit: 100,
            coupon: {
                is_active: false,
                max_redemptions: 50,
                expires_at: "2026-03-15T10:00:00Z",
            },
        });
    });

    it("does not include coupon in edit mode", () => {
        const payload = buildPromotionMutationPayload(
            {
                name: "Sale",
                code: "sale",
                type: "fixed",
                value: 5,
                is_active: false,
                starts_at: "",
                ends_at: "",
                usage_limit: "invalid",
                coupon_is_active: true,
                coupon_max_redemptions: "100",
                coupon_expires_at: "",
            },
            true,
        );

        expect(payload.coupon).toBeUndefined();
        expect(payload.code).toBe("SALE");
        expect(payload.usage_limit).toBeNull();
    });

    it("builds coupon create payload", () => {
        const payload = buildCouponCreatePayload({
            code: "  test 2 ",
            is_active: true,
            max_redemptions: "",
            expires_at: "",
        });

        expect(payload).toEqual({
            code: "TEST 2",
            is_active: true,
            max_redemptions: null,
            expires_at: null,
        });
    });
});
