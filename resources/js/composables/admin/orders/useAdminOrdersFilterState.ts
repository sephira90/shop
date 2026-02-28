import { reactive } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import {
    buildAdminOrderListParams,
    parseAdminOrderFiltersFromRouteQuery,
    type AdminOrderFilters,
    type AdminOrderRouteFilters,
} from "@/queries/admin/orders";
import type { AdminOrderListParams } from "@/types/admin-orders";

const createDefaultRouteFilters = (): AdminOrderRouteFilters => ({
    search: "",
    orderStatus: "all",
    paymentStatus: "all",
    shipmentStatus: "all",
    page: 1,
});

type OrderFilterSourceTuple = [string, string, string, string];

export const useAdminOrdersFilterState = (routeSync?: AdminRouteSyncOptions) => {
    const initialRouteFilters = routeSync
        ? parseAdminOrderFiltersFromRouteQuery(routeSync.route.query)
        : createDefaultRouteFilters();
    const filters = reactive<AdminOrderFilters>({
        search: initialRouteFilters.search,
        orderStatus: initialRouteFilters.orderStatus,
        paymentStatus: initialRouteFilters.paymentStatus,
        shipmentStatus: initialRouteFilters.shipmentStatus,
    });

    const buildListParams = (targetPage: number): AdminOrderListParams =>
        buildAdminOrderListParams(targetPage, filters);

    const filterSource = (): OrderFilterSourceTuple => [
        filters.search,
        filters.orderStatus,
        filters.paymentStatus,
        filters.shipmentStatus,
    ];

    const applyParsedFilters = (parsed: AdminOrderRouteFilters): number => {
        filters.search = parsed.search;
        filters.orderStatus = parsed.orderStatus;
        filters.paymentStatus = parsed.paymentStatus;
        filters.shipmentStatus = parsed.shipmentStatus;

        return parsed.page;
    };

    const readFiltersForPage = (targetPage: number): AdminOrderRouteFilters => ({
        search: filters.search,
        orderStatus: filters.orderStatus,
        paymentStatus: filters.paymentStatus,
        shipmentStatus: filters.shipmentStatus,
        page: targetPage,
    });

    return {
        initialPage: initialRouteFilters.page,
        filters,
        buildListParams,
        filterSource,
        applyParsedFilters,
        readFiltersForPage,
    };
};
