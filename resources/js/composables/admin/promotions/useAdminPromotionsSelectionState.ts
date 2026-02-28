import { ref } from "vue";

import type { Promotion } from "@/types/admin-promotions";

export const useAdminPromotionsSelectionState = () => {
    const selectedPromotionId = ref<number | null>(null);

    const syncSelectionWithPromotions = (promotions: readonly Promotion[]): void => {
        const selectedId = selectedPromotionId.value;
        const hasSelected = selectedId !== null;
        const selectionExists =
            hasSelected && promotions.some((promotion) => promotion.id === selectedId);

        if (!selectionExists) {
            selectedPromotionId.value = promotions[0]?.id ?? null;
        }
    };

    const clearSelection = (): void => {
        selectedPromotionId.value = null;
    };

    const selectPromotion = (promotionId: number): void => {
        selectedPromotionId.value = promotionId;
    };

    return {
        selectedPromotionId,
        syncSelectionWithPromotions,
        clearSelection,
        selectPromotion,
    };
};
