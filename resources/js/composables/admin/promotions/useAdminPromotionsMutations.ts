import { ref } from "vue";

import {
    createPromotion as createPromotionRequest,
    createPromotionCoupon,
    deletePromotion as deletePromotionRequest,
    updateCoupon as updateCouponRequest,
    updatePromotion as updatePromotionRequest,
} from "@/api/admin/promotions";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import type {
    Coupon,
    CouponFormState,
    Promotion,
    PromotionFormState,
} from "@/types/admin-promotions";
import { normalizeDatetimeForInput } from "@/utils/datetime";
import {
    buildCouponCreatePayload,
    buildPromotionMutationPayload,
    createCouponFormState,
    createPromotionFormState,
} from "@/validators/admin/promotions";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

import type { useAdminPromotionsQuery } from "./useAdminPromotionsQuery";

interface AdminPromotionsMutationNoticeAdapter {
    clearNotice: () => void;
    showSuccess: (message: string) => void;
    showError: (message: string) => void;
}

interface UseAdminPromotionsMutationsOptions {
    query: ReturnType<typeof useAdminPromotionsQuery>;
    executeMutation: ExecuteAdminMutation;
    notice: AdminPromotionsMutationNoticeAdapter;
    uiEffects: AdminUiEffectsAdapter;
}

export const useAdminPromotionsMutations = ({
    query,
    executeMutation,
    notice,
    uiEffects,
}: UseAdminPromotionsMutationsOptions) => {
    const isSubmittingPromotion = ref(false);
    const isSubmittingCoupon = ref(false);
    const isDeletingPromotionId = ref<number | null>(null);
    const updatingCouponId = ref<number | null>(null);
    const editingPromotionId = ref<number | null>(null);
    const promotionForm = ref<PromotionFormState>(createPromotionFormState());
    const couponForm = ref<CouponFormState>(createCouponFormState());

    const clearPromotionFormState = (): void => {
        editingPromotionId.value = null;
        promotionForm.value = createPromotionFormState();
    };

    const resetPromotionForm = (): void => {
        clearPromotionFormState();
        notice.clearNotice();
    };

    const resetPromotionFormKeepNotice = (): void => {
        clearPromotionFormState();
    };

    const startEditPromotion = (promotion: Promotion): void => {
        editingPromotionId.value = promotion.id;
        query.selectedPromotionId.value = promotion.id;
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
        notice.clearNotice();
        uiEffects.scrollToTop();
    };

    const submitPromotion = async (): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                isSubmittingPromotion.value = pending;
            },
            errorMessage: "Unable to save promotion.",
            run: async () => {
                const payload = buildPromotionMutationPayload(
                    promotionForm.value,
                    editingPromotionId.value !== null,
                );

                if (editingPromotionId.value !== null) {
                    const promotionId = editingPromotionId.value;
                    await updatePromotionRequest(promotionId, payload);
                    notice.showSuccess("Campaign updated.");
                    await query.loadPromotions(query.page.value);
                    resetPromotionFormKeepNotice();
                    query.selectedPromotionId.value = promotionId;
                } else {
                    await createPromotionRequest(payload);
                    notice.showSuccess("Campaign created.");
                    await query.loadPromotions(1);
                    resetPromotionFormKeepNotice();
                }
            },
        });
    };

    const removePromotion = async (promotion: Promotion): Promise<void> => {
        const confirmed = await uiEffects.confirm(`Delete campaign "${promotion.name}"?`);
        if (!confirmed) {
            return;
        }

        await executeMutation<void>({
            setPending: (pending) => {
                isDeletingPromotionId.value = pending ? promotion.id : null;
            },
            errorMessage: "Unable to delete campaign.",
            run: async () => {
                await deletePromotionRequest(promotion.id);
                notice.showSuccess("Campaign deleted.");
                const nextPage =
                    query.promotions.value.length === 1 && query.page.value > 1
                        ? query.page.value - 1
                        : query.page.value;
                await query.loadPromotions(nextPage);
                if (editingPromotionId.value === promotion.id) {
                    resetPromotionFormKeepNotice();
                }
            },
        });
    };

    const createCoupon = async (): Promise<void> => {
        const promotion = query.selectedPromotion.value;

        if (!promotion) {
            notice.showError("Select promotion first.");
            return;
        }

        await executeMutation<void>({
            setPending: (pending) => {
                isSubmittingCoupon.value = pending;
            },
            errorMessage: "Unable to create coupon.",
            run: async () => {
                await createPromotionCoupon(
                    promotion.id,
                    buildCouponCreatePayload(couponForm.value),
                );
                notice.showSuccess("Coupon created.");
                couponForm.value = createCouponFormState();
                await query.loadPromotions(query.page.value);
            },
        });
    };

    const toggleCoupon = async (coupon: Coupon): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                updatingCouponId.value = pending ? coupon.id : null;
            },
            errorMessage: "Unable to update coupon.",
            run: async () => {
                await updateCouponRequest(coupon.id, {
                    is_active: !coupon.is_active,
                });
                notice.showSuccess("Coupon status updated.");
                await query.loadPromotions(query.page.value);
            },
        });
    };

    return {
        isSubmittingPromotion,
        isSubmittingCoupon,
        isDeletingPromotionId,
        updatingCouponId,
        editingPromotionId,
        promotionForm,
        couponForm,
        resetPromotionForm,
        startEditPromotion,
        submitPromotion,
        removePromotion,
        createCoupon,
        toggleCoupon,
    };
};
