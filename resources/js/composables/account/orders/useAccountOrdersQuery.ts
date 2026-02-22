import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import { listAccountOrders } from "@/api/account/orders";
import { useApiError } from "@/composables/useApiError";
import { usePaginationMeta } from "@/composables/usePaginationMeta";
import {
    useRouteSyncedPagination,
    type RouteQueryLike,
    type RouteQueryRouterLike,
} from "@/composables/useRouteSyncedPagination";
import {
    buildAccountOrdersListParams,
    buildAccountOrdersRouteQuery,
    isSameAccountOrdersRouteQuery,
    parseAccountOrdersFiltersFromRouteQuery,
} from "@/queries/account-orders";
import type { AccountOrder, AccountOrderStatusFilter } from "@/types/account-orders";

interface UseAccountOrdersQueryOptions {
    route?: RouteQueryLike;
    router?: RouteQueryRouterLike;
}

export const useAccountOrdersQuery = (options: UseAccountOrdersQueryOptions = {}) => {
    const route = options.route ?? useRoute();
    const router = options.router ?? useRouter();
    const { parseApiError } = useApiError();
    const { meta, applyMeta, resetMeta } = usePaginationMeta();
    const initialFilters = parseAccountOrdersFiltersFromRouteQuery(route.query);
    const orders = ref<AccountOrder[]>([]);
    const isLoading = ref(false);
    const loadError = ref("");
    const searchQuery = ref(initialFilters.searchQuery);
    const statusFilter = ref<AccountOrderStatusFilter>(initialFilters.statusFilter);
    const page = ref(initialFilters.page);
    let activeRequestId = 0;

    const fetchOrders = async (targetPage: number): Promise<void> => {
        const requestId = ++activeRequestId;
        isLoading.value = true;
        loadError.value = "";

        try {
            const response = await listAccountOrders(
                buildAccountOrdersListParams(targetPage, {
                    searchQuery: searchQuery.value,
                    statusFilter: statusFilter.value,
                }),
            );

            if (requestId !== activeRequestId) {
                return;
            }

            orders.value = response.data;
            applyMeta(response.meta);
            page.value = response.meta.current_page;
        } catch (error: unknown) {
            if (requestId !== activeRequestId) {
                return;
            }

            loadError.value = parseApiError(error, "Unable to load orders.");
            orders.value = [];
            resetMeta();
            page.value = 1;
        } finally {
            if (requestId === activeRequestId) {
                isLoading.value = false;
            }
        }
    };

    const routeSync = useRouteSyncedPagination({
        route,
        router,
        parseRouteQuery: parseAccountOrdersFiltersFromRouteQuery,
        buildRouteQuery: buildAccountOrdersRouteQuery,
        isSameRouteQuery: isSameAccountOrdersRouteQuery,
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
        fetchPage: fetchOrders,
        immediate: true,
    });
    const loadOrders = async (targetPage = page.value): Promise<void> => {
        await routeSync.load(targetPage);
    };

    return {
        orders,
        searchQuery,
        statusFilter,
        page,
        meta,
        isLoading,
        loadError,
        loadOrders,
    };
};
