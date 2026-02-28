import { computed } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { useAdminUiMutationContext } from "@/composables/admin/useAdminUiMutationContext";
import { useAuthStore } from "@/stores/auth";
import type { ProductStatus } from "@/types/admin-products";
import {
    productStatusBadgeTone as resolveProductStatusBadgeTone,
    type BadgeTone,
} from "@/utils/order-presentation";

import { useAdminProductsMutations } from "./useAdminProductsMutations";
import { useAdminProductsQuery } from "./useAdminProductsQuery";

interface UseAdminProductsOptions {
    uiEffects?: Partial<AdminUiEffectsAdapter>;
    routeSync?: AdminRouteSyncOptions;
}

export const useAdminProductsViewModel = (options: UseAdminProductsOptions = {}) => {
    const authStore = useAuthStore();
    const { uiEffects, mutationContext } = useAdminUiMutationContext(options.uiEffects);
    const canDeleteProducts = computed<boolean>(() => authStore.hasRole("admin"));
    const query = useAdminProductsQuery(mutationContext.queryNotice, options.routeSync);
    const mutations = useAdminProductsMutations({
        query,
        executeMutation: mutationContext.executeMutation,
        canDeleteProducts,
        notice: mutationContext.mutationNotice,
        uiEffects,
    });

    const statusBadgeTone = (status: ProductStatus): BadgeTone => {
        return resolveProductStatusBadgeTone(status);
    };

    return {
        notice: mutationContext.notice,
        canDeleteProducts,
        statusBadgeTone,
        ...query,
        ...mutations,
    };
};
