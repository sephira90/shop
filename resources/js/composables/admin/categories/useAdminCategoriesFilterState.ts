import { ref } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import {
    buildAdminCategoryListParams,
    parseAdminCategoryFiltersFromRouteQuery,
    type AdminCategoryRouteFilters,
} from "@/queries/admin/categories";
import type { AdminCategoryListParams, CategoryStatusFilter } from "@/types/admin-categories";

type CategoryFilterSourceTuple = [string, CategoryStatusFilter];

export const useAdminCategoriesFilterState = (routeSync?: AdminRouteSyncOptions) => {
    const initialRouteFilters = routeSync
        ? parseAdminCategoryFiltersFromRouteQuery(routeSync.route.query)
        : { searchQuery: "", statusFilter: "all" as CategoryStatusFilter, page: 1 };
    const searchQuery = ref(initialRouteFilters.searchQuery);
    const statusFilter = ref<CategoryStatusFilter>(initialRouteFilters.statusFilter);

    const buildListParams = (targetPage: number): AdminCategoryListParams =>
        buildAdminCategoryListParams(targetPage, {
            searchQuery: searchQuery.value,
            statusFilter: statusFilter.value,
        });

    const filterSource = (): CategoryFilterSourceTuple => [searchQuery.value, statusFilter.value];

    const applyParsedFilters = (parsed: AdminCategoryRouteFilters): number => {
        searchQuery.value = parsed.searchQuery;
        statusFilter.value = parsed.statusFilter;

        return parsed.page;
    };

    const readFiltersForPage = (targetPage: number): AdminCategoryRouteFilters => ({
        searchQuery: searchQuery.value,
        statusFilter: statusFilter.value,
        page: targetPage,
    });

    return {
        initialPage: initialRouteFilters.page,
        searchQuery,
        statusFilter,
        buildListParams,
        filterSource,
        applyParsedFilters,
        readFiltersForPage,
    };
};
