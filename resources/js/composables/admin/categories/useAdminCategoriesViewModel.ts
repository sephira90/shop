import { computed } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { resolveAdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { useAdminMutation } from "@/composables/useAdminMutation";
import { useAdminNotice } from "@/composables/useAdminNotice";
import { useAuthStore } from "@/stores/auth";
import type { AdminCategory } from "@/types/admin-categories";

import { useAdminCategoriesMutations } from "./useAdminCategoriesMutations";
import { useAdminCategoriesQuery } from "./useAdminCategoriesQuery";

interface UseAdminCategoriesOptions {
    uiEffects?: Partial<AdminUiEffectsAdapter>;
    routeSync?: AdminRouteSyncOptions;
}

export const useAdminCategoriesViewModel = (options: UseAdminCategoriesOptions = {}) => {
    const authStore = useAuthStore();
    const uiEffects = resolveAdminUiEffectsAdapter(options.uiEffects);
    const { notice, clearNotice, showSuccess, showError, showApiError } = useAdminNotice();
    const { executeMutation } = useAdminMutation({
        clearNotice,
        showApiError,
    });
    const canDeleteCategories = computed<boolean>(() => authStore.hasRole("admin"));
    const query = useAdminCategoriesQuery(
        {
            clearNotice,
            showApiError,
        },
        options.routeSync,
    );
    const mutations = useAdminCategoriesMutations({
        query,
        executeMutation,
        canDeleteCategories,
        notice: {
            clearNotice,
            showSuccess,
            showError,
        },
        uiEffects,
    });
    const parentOptions = computed<AdminCategory[]>(() => {
        return query.categories.value.filter(
            (category) => category.id !== mutations.editingId.value,
        );
    });

    return {
        notice,
        canDeleteCategories,
        parentOptions,
        ...query,
        ...mutations,
    };
};
