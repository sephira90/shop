import { computed, reactive, ref } from "vue";

import { getAdminOrderDetail, listAdminOrders } from "@/api/admin/orders";
import type { ListResponse } from "@/api/response";
import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import { useRouteSyncedPagination } from "@/composables/useRouteSyncedPagination";
import { useServerPaginatedList } from "@/composables/useServerPaginatedList";
import { useServerListFilters } from "@/composables/useServerListFilters";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import {
    buildAdminOrderListParams,
    buildAdminOrderRouteQuery,
    isSameAdminOrderRouteQuery,
    parseAdminOrderFiltersFromRouteQuery,
    type AdminOrderFilters,
} from "@/queries/admin/orders";
import type {
    AdminOrderDetail,
    AdminOrderListParams,
    AdminOrderSummary,
} from "@/types/admin-orders";

export interface StatusDraft {
    status: string;
    payment_status: string;
    shipment_status: string;
    saving: boolean;
}

interface AdminOrdersQueryNoticeAdapter {
    clearNotice: () => void;
    showApiError: (error: unknown, fallback: string) => void;
}

interface UseAdminOrdersQueryOptions {
    notice: AdminOrdersQueryNoticeAdapter;
    executeMutation: ExecuteAdminMutation;
    routeSync?: AdminRouteSyncOptions;
}

const createDefaultStatusDraft = (): StatusDraft => ({
    status: "pending",
    payment_status: "pending",
    shipment_status: "pending",
    saving: false,
});

export const useAdminOrdersQuery = ({
    notice,
    executeMutation,
    routeSync,
}: UseAdminOrdersQueryOptions) => {
    const initialFilters = routeSync
        ? parseAdminOrderFiltersFromRouteQuery(routeSync.route.query)
        : {
              search: "",
              orderStatus: "all",
              paymentStatus: "all",
              shipmentStatus: "all",
              page: 1,
          };
    const filters = reactive<AdminOrderFilters>({
        search: initialFilters.search,
        orderStatus: initialFilters.orderStatus,
        paymentStatus: initialFilters.paymentStatus,
        shipmentStatus: initialFilters.shipmentStatus,
    });
    const selectedOrderId = ref<string | null>(null);
    const isDetailLoading = ref(false);
    const orderDetails = reactive<Record<string, AdminOrderDetail>>({});
    const selectedOrderDetail = ref<AdminOrderDetail | null>(null);
    const statusDrafts = reactive<Record<string, StatusDraft>>({});

    const ensureDraft = (order: AdminOrderSummary | AdminOrderDetail): StatusDraft => {
        if (!statusDrafts[order.id]) {
            statusDrafts[order.id] = {
                status: order.status,
                payment_status: order.payment_status,
                shipment_status: order.shipment_status,
                saving: false,
            };
        }

        return statusDrafts[order.id];
    };

    const syncDraftWithOrder = (order: AdminOrderSummary | AdminOrderDetail): void => {
        statusDrafts[order.id] = {
            status: order.status,
            payment_status: order.payment_status,
            shipment_status: order.shipment_status,
            saving: false,
        };
    };

    const loadOrderDetail = async (orderId: string, force = false): Promise<void> => {
        if (!force && orderDetails[orderId]) {
            selectedOrderDetail.value = orderDetails[orderId];
            return;
        }

        await executeMutation<AdminOrderDetail | null>({
            setPending: (pending) => {
                isDetailLoading.value = pending;
            },
            errorMessage: "Unable to load order details.",
            clearNotice: false,
            run: async () => getAdminOrderDetail(orderId),
            onSuccess: (detail) => {
                if (!detail) {
                    selectedOrderDetail.value = null;
                    return;
                }

                orderDetails[orderId] = detail;
                selectedOrderDetail.value = detail;
                syncDraftWithOrder(detail);
            },
            onError: (error: unknown) => {
                selectedOrderDetail.value = null;
                notice.showApiError(error, "Unable to load order details.");
            },
        });
    };

    const {
        items: orders,
        page,
        isLoading,
        meta,
        load: loadOrdersRaw,
    } = useServerPaginatedList<AdminOrderSummary, AdminOrderListParams>({
        buildParams: (targetPage) => buildAdminOrderListParams(targetPage, filters),
        fetchPage: listAdminOrders,
        ...(routeSync
            ? { initialPage: initialFilters.page }
            : {
                  filterSource: () => [
                      filters.search,
                      filters.orderStatus,
                      filters.paymentStatus,
                      filters.shipmentStatus,
                  ],
                  debounceMs: 300,
              }),
        resetOnError: true,
        onLoading: () => {
            notice.clearNotice();
        },
        onLoaded: async (response: ListResponse<AdminOrderSummary>) => {
            response.data.forEach((order) => {
                syncDraftWithOrder(order);
            });

            if (
                !selectedOrderId.value ||
                !response.data.some((order) => order.id === selectedOrderId.value)
            ) {
                selectedOrderId.value = response.data[0]?.id ?? null;
            }

            if (selectedOrderId.value) {
                await loadOrderDetail(selectedOrderId.value);
            } else {
                selectedOrderDetail.value = null;
            }
        },
        onError: (error: unknown) => {
            orders.value = [];
            selectedOrderId.value = null;
            selectedOrderDetail.value = null;
            notice.showApiError(error, "Unable to load orders.");
        },
    });
    const routePagination = useRouteSyncedPagination({
        route: routeSync?.route,
        router: routeSync?.router,
        parseRouteQuery: parseAdminOrderFiltersFromRouteQuery,
        buildRouteQuery: buildAdminOrderRouteQuery,
        isSameRouteQuery: isSameAdminOrderRouteQuery,
        applyParsedFilters: (parsed) => {
            filters.search = parsed.search;
            filters.orderStatus = parsed.orderStatus;
            filters.paymentStatus = parsed.paymentStatus;
            filters.shipmentStatus = parsed.shipmentStatus;
            page.value = parsed.page;
        },
        readFiltersForPage: (targetPage) => ({
            search: filters.search,
            orderStatus: filters.orderStatus,
            paymentStatus: filters.paymentStatus,
            shipmentStatus: filters.shipmentStatus,
            page: targetPage,
        }),
        fetchPage: loadOrdersRaw,
        immediate: false,
    });
    const loadOrders = async (targetPage = page.value): Promise<void> => {
        await routePagination.load(targetPage);
    };

    if (routeSync) {
        useServerListFilters(
            () => [
                filters.search,
                filters.orderStatus,
                filters.paymentStatus,
                filters.shipmentStatus,
            ],
            () => loadOrders(1),
            {
                debounceMs: 300,
            },
        );
    }

    const filteredOrders = computed<AdminOrderSummary[]>(() => orders.value);

    const selectedOrderSummary = computed<AdminOrderSummary | null>(() => {
        if (!selectedOrderId.value) {
            return null;
        }

        return orders.value.find((order) => order.id === selectedOrderId.value) ?? null;
    });

    const currentDraft = computed<StatusDraft>(() => {
        if (!selectedOrderDetail.value) {
            return createDefaultStatusDraft();
        }

        return ensureDraft(selectedOrderDetail.value);
    });

    const paidCount = computed<number>(
        () =>
            orders.value.filter(
                (order) => order.status === "paid" || order.payment_status === "captured",
            ).length,
    );
    const completedCount = computed<number>(
        () => orders.value.filter((order) => order.status === "completed").length,
    );
    const pendingPaymentCount = computed<number>(
        () => orders.value.filter((order) => order.payment_status === "pending").length,
    );

    const selectOrder = async (orderId: string): Promise<void> => {
        selectedOrderId.value = orderId;
        await loadOrderDetail(orderId);
    };

    return {
        filters,
        orders,
        page,
        isLoading,
        meta,
        loadOrders,
        selectedOrderId,
        isDetailLoading,
        orderDetails,
        selectedOrderDetail,
        statusDrafts,
        ensureDraft,
        syncDraftWithOrder,
        loadOrderDetail,
        selectOrder,
        filteredOrders,
        selectedOrderSummary,
        currentDraft,
        paidCount,
        completedCount,
        pendingPaymentCount,
    };
};
