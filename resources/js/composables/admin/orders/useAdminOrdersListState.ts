import { listAdminOrders } from "@/api/admin/orders";
import type { ListResponse } from "@/api/response";
import {
    type AdminRouteSyncOptions,
    useAdminRouteSyncedLoader,
} from "@/composables/admin/adminRouteSync";
import type { AdminQueryNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import { useServerPaginatedList } from "@/composables/useServerPaginatedList";
import {
    buildAdminOrderRouteQuery,
    isSameAdminOrderRouteQuery,
    parseAdminOrderFiltersFromRouteQuery,
    type AdminOrderRouteFilters,
} from "@/queries/admin/orders";
import type { AdminOrderListParams, AdminOrderSummary } from "@/types/admin-orders";

interface AdminOrdersListFilterAdapter {
    initialPage: number;
    buildListParams: (targetPage: number) => AdminOrderListParams;
    filterSource: () => [string, string, string, string];
    applyParsedFilters: (parsed: AdminOrderRouteFilters) => number;
    readFiltersForPage: (targetPage: number) => AdminOrderRouteFilters;
}

interface AdminOrdersListDetailAdapter {
    syncSelectionWithOrderList: (orders: readonly AdminOrderSummary[]) => Promise<void>;
    resetOnOrderListError: () => void;
}

interface UseAdminOrdersListStateOptions {
    notice: AdminQueryNoticeAdapter;
    filterState: AdminOrdersListFilterAdapter;
    detailState: AdminOrdersListDetailAdapter;
    routeSync?: AdminRouteSyncOptions;
}

export const useAdminOrdersListState = ({
    notice,
    filterState,
    detailState,
    routeSync,
}: UseAdminOrdersListStateOptions) => {
    const {
        items: orders,
        page,
        isLoading,
        meta,
        load: loadOrdersRaw,
    } = useServerPaginatedList<AdminOrderSummary, AdminOrderListParams>({
        buildParams: filterState.buildListParams,
        fetchPage: listAdminOrders,
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
        onLoaded: async (response: ListResponse<AdminOrderSummary>) => {
            await detailState.syncSelectionWithOrderList(response.data);
        },
        onError: (error: unknown) => {
            detailState.resetOnOrderListError();
            orders.value = [];
            notice.showApiError(error, "Unable to load orders.");
        },
    });

    const { load: loadOrders } = useAdminRouteSyncedLoader({
        routeSync,
        page,
        fetchPage: loadOrdersRaw,
        parseRouteQuery: parseAdminOrderFiltersFromRouteQuery,
        buildRouteQuery: buildAdminOrderRouteQuery,
        isSameRouteQuery: isSameAdminOrderRouteQuery,
        applyParsedFilters: (parsed) => {
            page.value = filterState.applyParsedFilters(parsed);
        },
        readFiltersForPage: filterState.readFiltersForPage,
        filterSource: filterState.filterSource,
        debounceMs: 300,
    });

    return {
        orders,
        page,
        isLoading,
        meta,
        loadOrders,
    };
};
