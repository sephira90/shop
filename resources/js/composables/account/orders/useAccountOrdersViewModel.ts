import { computed, ref, watch } from "vue";

import type { RouteQueryLike, RouteQueryRouterLike } from "@/composables/useRouteSyncedPagination";
import type {
    AccountOrderAddress,
    AccountOrderDetail,
    AccountOrderSummary,
} from "@/types/account-orders";
import { formatPrice as formatCurrency } from "@/utils/format";
import {
    formatOrderAddress,
    formatOrderDate,
    orderStatusTone as resolveOrderStatusTone,
    paymentStatusTone as resolvePaymentStatusTone,
    shipmentStatusTone as resolveShipmentStatusTone,
    type StatusTone,
} from "@/utils/order-presentation";

import { useAccountOrderDetailsState } from "./useAccountOrderDetailsState";
import { useAccountOrdersQuery } from "./useAccountOrdersQuery";

interface UseAccountOrdersOptions {
    route?: RouteQueryLike;
    router?: RouteQueryRouterLike;
}

export const useAccountOrdersViewModel = (options: UseAccountOrdersOptions = {}) => {
    const query = useAccountOrdersQuery(options);
    const detailsState = useAccountOrderDetailsState();
    const expandedOrderIds = ref<string[]>([]);

    watch(query.orders, () => {
        expandedOrderIds.value = [];
        detailsState.resetDetails();
    });

    const filteredOrders = computed<AccountOrderSummary[]>(() => query.orders.value);
    const loadedTotal = computed<number>(() =>
        query.orders.value.reduce((sum, order) => sum + Number(order.total ?? 0), 0),
    );
    const paidCount = computed<number>(
        () =>
            query.orders.value.filter(
                (order) => order.status === "paid" || order.payment_status === "captured",
            ).length,
    );
    const shipmentActiveCount = computed<number>(
        () =>
            query.orders.value.filter((order) =>
                ["packed", "shipped"].includes(order.shipment_status),
            ).length,
    );

    const applyFilters = async (): Promise<void> => {
        await query.loadOrders(1);
    };

    const isExpanded = (orderId: string): boolean => expandedOrderIds.value.includes(orderId);

    const toggleDetails = async (orderId: string): Promise<void> => {
        if (isExpanded(orderId)) {
            expandedOrderIds.value = expandedOrderIds.value.filter((id) => id !== orderId);
            return;
        }

        expandedOrderIds.value = [...expandedOrderIds.value, orderId];
        await detailsState.loadOrderDetail(orderId);
    };

    const totalItems = (order: AccountOrderDetail | null): number =>
        order?.items.reduce((sum, item) => sum + item.quantity, 0) ?? 0;

    const formatPrice = (value: number, currency = "USD"): string =>
        formatCurrency(value, currency);
    const formatDate = (value: string | null): string => formatOrderDate(value, "Unknown date");
    const formatAddress = (address: AccountOrderAddress | null): string =>
        formatOrderAddress(address);
    const orderStatusTone = (status: string): StatusTone => resolveOrderStatusTone(status);
    const paymentStatusTone = (status: string): StatusTone => resolvePaymentStatusTone(status);
    const shipmentStatusTone = (status: string): StatusTone => resolveShipmentStatusTone(status);

    return {
        orders: query.orders,
        filteredOrders,
        expandedOrderIds,
        orderDetails: detailsState.orderDetails,
        searchQuery: query.searchQuery,
        statusFilter: query.statusFilter,
        page: query.page,
        meta: query.meta,
        isLoading: query.isLoading,
        loadError: query.loadError,
        loadedTotal,
        paidCount,
        shipmentActiveCount,
        loadOrders: query.loadOrders,
        applyFilters,
        isExpanded,
        isDetailLoading: detailsState.isDetailLoading,
        getOrderDetail: detailsState.getOrderDetail,
        getDetailError: detailsState.getDetailError,
        loadOrderDetail: detailsState.loadOrderDetail,
        toggleDetails,
        totalItems,
        formatPrice,
        formatDate,
        formatAddress,
        orderStatusTone,
        paymentStatusTone,
        shipmentStatusTone,
    };
};
