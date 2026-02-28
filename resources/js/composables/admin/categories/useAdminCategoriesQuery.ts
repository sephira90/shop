import { computed } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import type { AdminQueryNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { AdminCategory } from "@/types/admin-categories";

import { useAdminCategoriesFilterState } from "./useAdminCategoriesFilterState";
import { useAdminCategoriesListState } from "./useAdminCategoriesListState";

export const useAdminCategoriesQuery = (
    notice: AdminQueryNoticeAdapter,
    routeSync?: AdminRouteSyncOptions,
) => {
    const filterState = useAdminCategoriesFilterState(routeSync);
    const { categories, page, isLoading, meta, loadCategories } = useAdminCategoriesListState({
        notice,
        filterState,
        routeSync,
    });

    const filteredCategories = computed<AdminCategory[]>(() => categories.value);

    return {
        categories,
        page,
        isLoading,
        meta,
        searchQuery: filterState.searchQuery,
        statusFilter: filterState.statusFilter,
        filteredCategories,
        loadCategories,
    };
};
