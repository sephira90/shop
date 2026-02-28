import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { useAdminUiMutationContext } from "@/composables/admin/useAdminUiMutationContext";

import { useAdminPromotionsMutations } from "./useAdminPromotionsMutations";
import { useAdminPromotionsQuery } from "./useAdminPromotionsQuery";

interface UseAdminPromotionsOptions {
    uiEffects?: Partial<AdminUiEffectsAdapter>;
    routeSync?: AdminRouteSyncOptions;
}

export const useAdminPromotionsViewModel = (options: UseAdminPromotionsOptions = {}) => {
    const { uiEffects, mutationContext } = useAdminUiMutationContext(options.uiEffects);
    const query = useAdminPromotionsQuery(mutationContext.queryNotice, options.routeSync);
    const mutations = useAdminPromotionsMutations({
        query,
        executeMutation: mutationContext.executeMutation,
        notice: mutationContext.mutationNotice,
        uiEffects,
    });

    return {
        notice: mutationContext.notice,
        ...query,
        ...mutations,
    };
};
