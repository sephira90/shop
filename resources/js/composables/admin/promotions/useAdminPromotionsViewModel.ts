import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { resolveAdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { useAdminMutation } from "@/composables/useAdminMutation";
import { useAdminNotice } from "@/composables/useAdminNotice";

import { useAdminPromotionsMutations } from "./useAdminPromotionsMutations";
import { useAdminPromotionsQuery } from "./useAdminPromotionsQuery";

interface UseAdminPromotionsOptions {
    uiEffects?: Partial<AdminUiEffectsAdapter>;
    routeSync?: AdminRouteSyncOptions;
}

export const useAdminPromotionsViewModel = (options: UseAdminPromotionsOptions = {}) => {
    const uiEffects = resolveAdminUiEffectsAdapter(options.uiEffects);
    const { notice, clearNotice, showSuccess, showError, showApiError } = useAdminNotice();
    const { executeMutation } = useAdminMutation({
        clearNotice,
        showApiError,
    });
    const query = useAdminPromotionsQuery(
        {
            clearNotice,
            showApiError,
        },
        options.routeSync,
    );
    const mutations = useAdminPromotionsMutations({
        query,
        executeMutation,
        notice: {
            clearNotice,
            showSuccess,
            showError,
        },
        uiEffects,
    });

    return {
        notice,
        ...query,
        ...mutations,
    };
};
