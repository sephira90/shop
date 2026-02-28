import { computed } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { useAdminUiMutationContext } from "@/composables/admin/useAdminUiMutationContext";
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
    const { uiEffects, mutationContext } = useAdminUiMutationContext(options.uiEffects);
    const canDeleteCategories = computed<boolean>(() => authStore.hasRole("admin"));
    const query = useAdminCategoriesQuery(mutationContext.queryNotice, options.routeSync);
    const mutations = useAdminCategoriesMutations({
        query,
        executeMutation: mutationContext.executeMutation,
        canDeleteCategories,
        notice: mutationContext.mutationNotice,
        uiEffects,
    });
    const parentOptions = computed<AdminCategory[]>(() => {
        return query.categories.value.filter(
            (category) => category.id !== mutations.editingId.value,
        );
    });

    return {
        notice: mutationContext.notice,
        canDeleteCategories,
        parentOptions,
        ...query,
        ...mutations,
    };
};
