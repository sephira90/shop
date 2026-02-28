import { listAdminCategories } from "@/api/admin/categories";
import {
    type AdminRouteSyncOptions,
    useAdminRouteSyncedLoader,
} from "@/composables/admin/adminRouteSync";
import type { AdminQueryNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import { useServerPaginatedList } from "@/composables/useServerPaginatedList";
import {
    buildAdminCategoryRouteQuery,
    isSameAdminCategoryRouteQuery,
    parseAdminCategoryFiltersFromRouteQuery,
    type AdminCategoryRouteFilters,
} from "@/queries/admin/categories";
import type {
    AdminCategory,
    AdminCategoryListParams,
    CategoryStatusFilter,
} from "@/types/admin-categories";

type CategoryFilterSourceTuple = [string, CategoryStatusFilter];

interface AdminCategoriesListFilterAdapter {
    initialPage: number;
    buildListParams: (targetPage: number) => AdminCategoryListParams;
    filterSource: () => CategoryFilterSourceTuple;
    applyParsedFilters: (parsed: AdminCategoryRouteFilters) => number;
    readFiltersForPage: (targetPage: number) => AdminCategoryRouteFilters;
}

interface UseAdminCategoriesListStateOptions {
    notice: AdminQueryNoticeAdapter;
    filterState: AdminCategoriesListFilterAdapter;
    routeSync?: AdminRouteSyncOptions;
}

export const useAdminCategoriesListState = ({
    notice,
    filterState,
    routeSync,
}: UseAdminCategoriesListStateOptions) => {
    const {
        items: categories,
        page,
        isLoading,
        meta,
        load: loadCategoriesRaw,
    } = useServerPaginatedList<AdminCategory, AdminCategoryListParams>({
        buildParams: filterState.buildListParams,
        fetchPage: listAdminCategories,
        ...(routeSync
            ? { initialPage: filterState.initialPage }
            : {
                  filterSource: filterState.filterSource,
                  debounceMs: 300,
              }),
        resetOnError: true,
        onLoading: () => {
            notice.clearNotice();
        },
        onError: (error: unknown) => {
            notice.showApiError(error, "Unable to load categories.");
        },
    });

    const { load: loadCategories } = useAdminRouteSyncedLoader({
        routeSync,
        page,
        fetchPage: loadCategoriesRaw,
        parseRouteQuery: parseAdminCategoryFiltersFromRouteQuery,
        buildRouteQuery: buildAdminCategoryRouteQuery,
        isSameRouteQuery: isSameAdminCategoryRouteQuery,
        applyParsedFilters: (parsed) => {
            page.value = filterState.applyParsedFilters(parsed);
        },
        readFiltersForPage: filterState.readFiltersForPage,
        filterSource: filterState.filterSource,
        debounceMs: 300,
    });

    return {
        categories,
        page,
        isLoading,
        meta,
        loadCategories,
    };
};
