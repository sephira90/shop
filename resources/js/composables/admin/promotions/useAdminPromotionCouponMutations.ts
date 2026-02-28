import { ref, type Ref } from "vue";

import { createPromotionCoupon, updateCoupon as updateCouponRequest } from "@/api/admin/promotions";
import { executeAdminActionMutationPipeline } from "@/composables/admin/adminActionMutationPipeline";
import type { AdminSubmitNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import type { Coupon, CouponFormState, Promotion } from "@/types/admin-promotions";
import { buildCouponCreatePayload } from "@/validators/admin/promotions";

interface AdminPromotionCouponQueryAdapter {
    selectedPromotion: Ref<Promotion | null>;
    page: Ref<number>;
    loadPromotions: (targetPage?: number) => Promise<void>;
}

interface AdminPromotionCouponFormAdapter {
    couponForm: Ref<CouponFormState>;
    resetCouponForm: () => void;
}

interface UseAdminPromotionCouponMutationsOptions {
    query: AdminPromotionCouponQueryAdapter;
    couponFormState: AdminPromotionCouponFormAdapter;
    executeMutation: ExecuteAdminMutation;
    notice: AdminSubmitNoticeAdapter;
}

export const useAdminPromotionCouponMutations = ({
    query,
    couponFormState,
    executeMutation,
    notice,
}: UseAdminPromotionCouponMutationsOptions) => {
    const isSubmittingCoupon = ref(false);
    const updatingCouponId = ref<number | null>(null);

    const createCoupon = async (): Promise<void> => {
        const promotion = query.selectedPromotion.value;

        if (!promotion) {
            notice.showError("Select promotion first.");
            return;
        }

        await executeAdminActionMutationPipeline<void>({
            executeMutation,
            setPending: (pending) => {
                isSubmittingCoupon.value = pending;
            },
            errorMessage: "Unable to create coupon.",
            run: async () => {
                await createPromotionCoupon(
                    promotion.id,
                    buildCouponCreatePayload(couponFormState.couponForm.value),
                );
            },
            resolveSuccessMessage: () => "Coupon created.",
            showSuccess: notice.showSuccess,
            afterSuccess: async () => {
                couponFormState.resetCouponForm();
                await query.loadPromotions(query.page.value);
            },
        });
    };

    const toggleCoupon = async (coupon: Coupon): Promise<void> => {
        await executeAdminActionMutationPipeline<void>({
            executeMutation,
            setPending: (pending) => {
                updatingCouponId.value = pending ? coupon.id : null;
            },
            errorMessage: "Unable to update coupon.",
            run: async () => {
                await updateCouponRequest(coupon.id, {
                    is_active: !coupon.is_active,
                });
            },
            resolveSuccessMessage: () => "Coupon status updated.",
            showSuccess: notice.showSuccess,
            afterSuccess: async () => {
                await query.loadPromotions(query.page.value);
            },
        });
    };

    return {
        isSubmittingCoupon,
        updatingCouponId,
        createCoupon,
        toggleCoupon,
    };
};
