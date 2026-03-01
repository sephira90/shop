import { computed, watch } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { useAdminUiMutationContext } from "@/composables/admin/useAdminUiMutationContext";
import { useAuthStore } from "@/stores/auth";

import { useAdminCategoryOptionsState } from "./useAdminCategoryOptionsState";
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
    const {
        categoryOptions: parentOptions,
        isLoadingCategoryOptions: isLoadingParentOptions,
        loadCategoryOptions,
    } = useAdminCategoryOptionsState(mutationContext.queryNotice);

    const loadParentOptions = async (): Promise<void> => {
        await loadCategoryOptions({
            exclude_id: mutations.editingId.value ?? undefined,
        });
    };

    watch(mutations.editingId, () => {
        void loadParentOptions();
    });

    return {
        notice: mutationContext.notice,
        canDeleteCategories,
        parentOptions,
        isLoadingParentOptions,
        loadParentOptions,
        ...query,
        ...mutations,
    };
};
