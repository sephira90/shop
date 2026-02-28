import { describe, expect, it, vi } from "vitest";
import { effectScope, ref } from "vue";

import { useAdminPromotionFormState } from "@/composables/admin/promotions/useAdminPromotionFormState";
import { normalizeDatetimeForInput } from "@/utils/datetime";
import type { Promotion } from "@/types/admin-promotions";

const buildPromotion = (id: number): Promotion => ({
    id,
    name: `Campaign ${id}`,
    code: `CODE-${id}`,
    type: "percent",
    value: 15,
    is_active: true,
    usage_limit: 25,
    usage_count: 3,
    starts_at: "2026-02-28T09:00:00Z",
    ends_at: "2026-03-10T09:00:00Z",
    coupons: [],
});

describe("useAdminPromotionFormState", () => {
    it("hydrates promotion form for edit and syncs selected promotion id", () => {
        const clearNotice = vi.fn();
        const scrollToTop = vi.fn();
        const selectedPromotionId = ref<number | null>(null);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotionFormState({
                clearNotice,
                scrollToTop,
                selectedPromotionId,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        const promotion = buildPromotion(8);
        api.startEditPromotion(promotion);

        expect(api.editingPromotionId.value).toBe(8);
        expect(selectedPromotionId.value).toBe(8);
        expect(api.promotionForm.value.name).toBe("Campaign 8");
        expect(api.promotionForm.value.code).toBe("CODE-8");
        expect(api.promotionForm.value.value).toBe(15);
        expect(api.promotionForm.value.usage_limit).toBe("25");
        expect(api.promotionForm.value.starts_at).toBe(
            normalizeDatetimeForInput(promotion.starts_at),
        );
        expect(api.promotionForm.value.ends_at).toBe(normalizeDatetimeForInput(promotion.ends_at));
        expect(clearNotice).toHaveBeenCalledTimes(1);
        expect(scrollToTop).toHaveBeenCalledTimes(1);

        scope.stop();
    });

    it("resets form to defaults and clears edit mode", () => {
        const clearNotice = vi.fn();
        const selectedPromotionId = ref<number | null>(null);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotionFormState({
                clearNotice,
                scrollToTop: vi.fn(),
                selectedPromotionId,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.startEditPromotion(buildPromotion(3));
        api.resetPromotionForm();

        expect(api.editingPromotionId.value).toBeNull();
        expect(api.promotionForm.value.name).toBe("");
        expect(api.promotionForm.value.type).toBe("percent");
        expect(api.promotionForm.value.value).toBe(10);
        expect(api.promotionForm.value.usage_limit).toBe("");
        expect(clearNotice).toHaveBeenCalledTimes(2);

        scope.stop();
    });
});
