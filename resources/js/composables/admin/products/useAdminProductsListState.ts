import type { Ref } from "vue";

import { listAdminProducts } from "@/api/admin/products";
import {
    type AdminRouteSyncOptions,
    useAdminRouteSyncedLoader,
} from "@/composables/admin/adminRouteSync";
import type { AdminQueryNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import { useServerPaginatedList } from "@/composables/useServerPaginatedList";
import {
    buildAdminProductRouteQuery,
    isSameAdminProductRouteQuery,
    parseAdminProductFiltersFromRouteQuery,
    type AdminProductRouteFilters,
} from "@/queries/admin/products";
import type { AdminProduct, AdminProductListParams } from "@/types/admin-products";

interface AdminProductsListFilterAdapter {
    initialPage: number;
    searchQuery: Ref<string>;
    buildListParams: (targetPage: number) => AdminProductListParams;
    applyParsedFilters: (parsed: AdminProductRouteFilters) => number;
    readFiltersForPage: (targetPage: number) => AdminProductRouteFilters;
}

interface UseAdminProductsListStateOptions {
    notice: AdminQueryNoticeAdapter;
    filterState: AdminProductsListFilterAdapter;
    routeSync?: AdminRouteSyncOptions;
}

export const useAdminProductsListState = ({
    notice,
    filterState,
    routeSync,
}: UseAdminProductsListStateOptions) => {
    const {
        items: products,
        page,
        isLoading,
        meta,
        load: loadProductsRaw,
    } = useServerPaginatedList<AdminProduct, AdminProductListParams>({
        buildParams: filterState.buildListParams,
        fetchPage: listAdminProducts,
        ...(routeSync
            ? { initialPage: filterState.initialPage }
            : {
                  filterSource: filterState.searchQuery,
                  debounceMs: 300,
              }),
        onLoading: () => {
            notice.clearNotice();
        },
        onError: (error: unknown) => {
            notice.showApiError(error, "Unable to load products.");
        },
    });

    const { load: loadProducts } = useAdminRouteSyncedLoader({
        routeSync,
        page,
        fetchPage: loadProductsRaw,
        parseRouteQuery: parseAdminProductFiltersFromRouteQuery,
        buildRouteQuery: buildAdminProductRouteQuery,
        isSameRouteQuery: isSameAdminProductRouteQuery,
        applyParsedFilters: (parsed) => {
            page.value = filterState.applyParsedFilters(parsed);
        },
        readFiltersForPage: filterState.readFiltersForPage,
        filterSource: filterState.searchQuery,
        debounceMs: 300,
    });

    return {
        products,
        page,
        isLoading,
        meta,
        loadProducts,
    };
};
