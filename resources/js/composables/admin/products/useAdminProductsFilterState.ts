import { ref } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import {
    buildAdminProductListParams,
    parseAdminProductFiltersFromRouteQuery,
    type AdminProductRouteFilters,
} from "@/queries/admin/products";
import type { AdminProductListParams } from "@/types/admin-products";

export const useAdminProductsFilterState = (routeSync?: AdminRouteSyncOptions) => {
    const initialRouteFilters = routeSync
        ? parseAdminProductFiltersFromRouteQuery(routeSync.route.query)
        : { searchQuery: "", page: 1 };
    const searchQuery = ref(initialRouteFilters.searchQuery);

    const buildListParams = (targetPage: number): AdminProductListParams =>
        buildAdminProductListParams(targetPage, {
            searchQuery: searchQuery.value,
        });

    const applyParsedFilters = (parsed: AdminProductRouteFilters): number => {
        searchQuery.value = parsed.searchQuery;

        return parsed.page;
    };

    const readFiltersForPage = (targetPage: number): AdminProductRouteFilters => ({
        searchQuery: searchQuery.value,
        page: targetPage,
    });

    return {
        initialPage: initialRouteFilters.page,
        searchQuery,
        buildListParams,
        applyParsedFilters,
        readFiltersForPage,
    };
};
