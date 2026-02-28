import { describe, expect, it } from "vitest";

import { useAdminPromotionCouponFormState } from "@/composables/admin/promotions/useAdminPromotionCouponFormState";

describe("useAdminPromotionCouponFormState", () => {
    it("resets coupon form to defaults", () => {
        const api = useAdminPromotionCouponFormState();

        api.couponForm.value.code = "VIP";
        api.couponForm.value.is_active = false;
        api.couponForm.value.max_redemptions = "100";

        api.resetCouponForm();

        expect(api.couponForm.value).toEqual({
            code: "",
            is_active: true,
            max_redemptions: "",
            expires_at: "",
        });
    });
});
