import { ref } from "vue";

import type { CouponFormState } from "@/types/admin-promotions";
import { createCouponFormState } from "@/validators/admin/promotions";

export const useAdminPromotionCouponFormState = () => {
    const couponForm = ref<CouponFormState>(createCouponFormState());

    const resetCouponForm = (): void => {
        couponForm.value = createCouponFormState();
    };

    return {
        couponForm,
        resetCouponForm,
    };
};
