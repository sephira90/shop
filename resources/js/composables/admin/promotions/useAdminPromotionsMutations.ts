import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import type { AdminMutationNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

import type { useAdminPromotionsQuery } from "./useAdminPromotionsQuery";
import { useAdminPromotionCouponFormState } from "./useAdminPromotionCouponFormState";
import { useAdminPromotionCouponMutations } from "./useAdminPromotionCouponMutations";
import { useAdminPromotionCrudMutations } from "./useAdminPromotionCrudMutations";
import { useAdminPromotionFormState } from "./useAdminPromotionFormState";

interface UseAdminPromotionsMutationsOptions {
    query: ReturnType<typeof useAdminPromotionsQuery>;
    executeMutation: ExecuteAdminMutation;
    notice: AdminMutationNoticeAdapter;
    uiEffects: AdminUiEffectsAdapter;
}

export const useAdminPromotionsMutations = ({
    query,
    executeMutation,
    notice,
    uiEffects,
}: UseAdminPromotionsMutationsOptions) => {
    const formState = useAdminPromotionFormState({
        clearNotice: notice.clearNotice,
        scrollToTop: uiEffects.scrollToTop,
        selectedPromotionId: query.selectedPromotionId,
    });
    const couponFormState = useAdminPromotionCouponFormState();
    const crudMutations = useAdminPromotionCrudMutations({
        query,
        formState,
        executeMutation,
        notice: {
            showSuccess: notice.showSuccess,
        },
        uiEffects,
    });
    const couponMutations = useAdminPromotionCouponMutations({
        query,
        couponFormState,
        executeMutation,
        notice: {
            showSuccess: notice.showSuccess,
            showError: notice.showError,
        },
    });

    return {
        isSubmittingPromotion: crudMutations.isSubmittingPromotion,
        isSubmittingCoupon: couponMutations.isSubmittingCoupon,
        isDeletingPromotionId: crudMutations.isDeletingPromotionId,
        updatingCouponId: couponMutations.updatingCouponId,
        editingPromotionId: formState.editingPromotionId,
        promotionForm: formState.promotionForm,
        couponForm: couponFormState.couponForm,
        resetPromotionForm: formState.resetPromotionForm,
        startEditPromotion: formState.startEditPromotion,
        submitPromotion: crudMutations.submitPromotion,
        removePromotion: crudMutations.removePromotion,
        createCoupon: couponMutations.createCoupon,
        toggleCoupon: couponMutations.toggleCoupon,
    };
};
