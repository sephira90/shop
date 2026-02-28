import { describe, expect, it } from "vitest";

import { useAdminPromotionsSelectionState } from "@/composables/admin/promotions/useAdminPromotionsSelectionState";
import type { Promotion } from "@/types/admin-promotions";

const buildPromotion = (id: number): Promotion => ({
    id,
    name: `Campaign ${id}`,
    code: `CODE-${id}`,
    type: "percent",
    value: 10,
    is_active: true,
    usage_limit: null,
    usage_count: 0,
    starts_at: null,
    ends_at: null,
    coupons: [],
});

describe("useAdminPromotionsSelectionState", () => {
    it("selects first promotion when selection is empty or outdated", () => {
        const state = useAdminPromotionsSelectionState();
        const promotions = [buildPromotion(1), buildPromotion(2)];

        state.syncSelectionWithPromotions(promotions);
        expect(state.selectedPromotionId.value).toBe(1);

        state.selectPromotion(2);
        state.syncSelectionWithPromotions([buildPromotion(3)]);
        expect(state.selectedPromotionId.value).toBe(3);
    });

    it("preserves existing selection and supports explicit clear", () => {
        const state = useAdminPromotionsSelectionState();
        const promotions = [buildPromotion(10), buildPromotion(20)];

        state.selectPromotion(20);
        state.syncSelectionWithPromotions(promotions);
        expect(state.selectedPromotionId.value).toBe(20);

        state.clearSelection();
        expect(state.selectedPromotionId.value).toBeNull();
    });
});
