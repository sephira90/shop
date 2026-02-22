import { computed } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { resolveAdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { useAdminMutation } from "@/composables/useAdminMutation";
import { useAdminNotice } from "@/composables/useAdminNotice";
import { useAuthStore } from "@/stores/auth";
import type { ProductStatus } from "@/types/admin-products";

import { useAdminProductsMutations } from "./useAdminProductsMutations";
import { useAdminProductsQuery } from "./useAdminProductsQuery";

interface UseAdminProductsOptions {
    uiEffects?: Partial<AdminUiEffectsAdapter>;
    routeSync?: AdminRouteSyncOptions;
}

export const useAdminProductsViewModel = (options: UseAdminProductsOptions = {}) => {
    const authStore = useAuthStore();
    const uiEffects = resolveAdminUiEffectsAdapter(options.uiEffects);
    const { notice, clearNotice, showSuccess, showError, showApiError } = useAdminNotice();
    const { executeMutation } = useAdminMutation({
        clearNotice,
        showApiError,
    });
    const canDeleteProducts = computed<boolean>(() => authStore.hasRole("admin"));
    const query = useAdminProductsQuery(
        {
            clearNotice,
            showApiError,
        },
        options.routeSync,
    );
    const mutations = useAdminProductsMutations({
        query,
        executeMutation,
        canDeleteProducts,
        notice: {
            clearNotice,
            showSuccess,
            showError,
        },
        uiEffects,
    });

    const statusBadgeClass = (status: ProductStatus): string => {
        return `badge--${status}`;
    };

    return {
        notice,
        canDeleteProducts,
        statusBadgeClass,
        ...query,
        ...mutations,
    };
};
