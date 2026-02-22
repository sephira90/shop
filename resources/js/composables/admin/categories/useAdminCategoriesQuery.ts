import { computed, ref } from "vue";

import { listAdminCategories } from "@/api/admin/categories";
import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import { useRouteSyncedPagination } from "@/composables/useRouteSyncedPagination";
import { useServerPaginatedList } from "@/composables/useServerPaginatedList";
import { useServerListFilters } from "@/composables/useServerListFilters";
import {
    buildAdminCategoryListParams,
    buildAdminCategoryRouteQuery,
    isSameAdminCategoryRouteQuery,
    parseAdminCategoryFiltersFromRouteQuery,
} from "@/queries/admin/categories";
import type {
    AdminCategory,
    AdminCategoryListParams,
    CategoryStatusFilter,
} from "@/types/admin-categories";

interface AdminCategoriesQueryNoticeAdapter {
    clearNotice: () => void;
    showApiError: (error: unknown, fallback: string) => void;
}

export const useAdminCategoriesQuery = (
    notice: AdminCategoriesQueryNoticeAdapter,
    routeSync?: AdminRouteSyncOptions,
) => {
    const initialFilters = routeSync
        ? parseAdminCategoryFiltersFromRouteQuery(routeSync.route.query)
        : { searchQuery: "", statusFilter: "all" as CategoryStatusFilter, page: 1 };
    const searchQuery = ref(initialFilters.searchQuery);
    const statusFilter = ref<CategoryStatusFilter>(initialFilters.statusFilter);

    const {
        items: categories,
        page,
        isLoading,
        meta,
        load: loadCategoriesRaw,
    } = useServerPaginatedList<AdminCategory, AdminCategoryListParams>({
        buildParams: (targetPage) =>
            buildAdminCategoryListParams(targetPage, {
                searchQuery: searchQuery.value,
                statusFilter: statusFilter.value,
            }),
        fetchPage: listAdminCategories,
        ...(routeSync
            ? { initialPage: initialFilters.page }
            : {
                  filterSource: () => [searchQuery.value, statusFilter.value],
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
    const routePagination = useRouteSyncedPagination({
        route: routeSync?.route,
        router: routeSync?.router,
        parseRouteQuery: parseAdminCategoryFiltersFromRouteQuery,
        buildRouteQuery: buildAdminCategoryRouteQuery,
        isSameRouteQuery: isSameAdminCategoryRouteQuery,
        applyParsedFilters: (parsed) => {
            searchQuery.value = parsed.searchQuery;
            statusFilter.value = parsed.statusFilter;
            page.value = parsed.page;
        },
        readFiltersForPage: (targetPage) => ({
            searchQuery: searchQuery.value,
            statusFilter: statusFilter.value,
            page: targetPage,
        }),
        fetchPage: loadCategoriesRaw,
        immediate: false,
    });
    const loadCategories = async (targetPage = page.value): Promise<void> => {
        await routePagination.load(targetPage);
    };

    if (routeSync) {
        useServerListFilters(
            () => [searchQuery.value, statusFilter.value],
            () => loadCategories(1),
            {
                debounceMs: 300,
            },
        );
    }

    const filteredCategories = computed<AdminCategory[]>(() => categories.value);

    return {
        categories,
        page,
        isLoading,
        meta,
        searchQuery,
        statusFilter,
        filteredCategories,
        loadCategories,
    };
};
