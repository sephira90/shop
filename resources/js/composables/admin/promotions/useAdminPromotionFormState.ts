import { ref, type Ref } from "vue";

import type { AdminClearNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { Promotion, PromotionFormState } from "@/types/admin-promotions";
import { normalizeDatetimeForInput } from "@/utils/datetime";
import { createPromotionFormState } from "@/validators/admin/promotions";

interface UseAdminPromotionFormStateOptions extends AdminClearNoticeAdapter {
    scrollToTop: () => void;
    selectedPromotionId: Ref<number | null>;
}

export const useAdminPromotionFormState = ({
    clearNotice,
    scrollToTop,
    selectedPromotionId,
}: UseAdminPromotionFormStateOptions) => {
    const editingPromotionId = ref<number | null>(null);
    const promotionForm = ref<PromotionFormState>(createPromotionFormState());

    const clearPromotionFormState = (): void => {
        editingPromotionId.value = null;
        promotionForm.value = createPromotionFormState();
    };

    const resetPromotionForm = (): void => {
        clearPromotionFormState();
        clearNotice();
    };

    const resetPromotionFormKeepNotice = (): void => {
        clearPromotionFormState();
    };

    const startEditPromotion = (promotion: Promotion): void => {
        editingPromotionId.value = promotion.id;
        selectedPromotionId.value = promotion.id;
        promotionForm.value = {
            name: promotion.name,
            code: promotion.code ?? "",
            type: promotion.type,
            value: Number(promotion.value),
            is_active: promotion.is_active,
            starts_at: normalizeDatetimeForInput(promotion.starts_at),
            ends_at: normalizeDatetimeForInput(promotion.ends_at),
            usage_limit: promotion.usage_limit !== null ? String(promotion.usage_limit) : "",
            coupon_is_active: true,
            coupon_max_redemptions: "",
            coupon_expires_at: "",
        };
        clearNotice();
        scrollToTop();
    };

    return {
        editingPromotionId,
        promotionForm,
        resetPromotionForm,
        resetPromotionFormKeepNotice,
        startEditPromotion,
    };
};
