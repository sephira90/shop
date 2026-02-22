import { computed, ref, watch } from "vue";

import type { RouteQueryLike, RouteQueryRouterLike } from "@/composables/useRouteSyncedPagination";
import type { AccountOrder, AccountOrderAddress } from "@/types/account-orders";
import {
    formatMoney,
    formatOrderAddress,
    formatOrderDate,
    orderStatusClass as resolveOrderStatusClass,
    paymentStatusClass as resolvePaymentStatusClass,
    shipmentStatusClass as resolveShipmentStatusClass,
} from "@/utils/order-presentation";

import { useAccountOrdersQuery } from "./useAccountOrdersQuery";

interface UseAccountOrdersOptions {
    route?: RouteQueryLike;
    router?: RouteQueryRouterLike;
}

export const useAccountOrdersViewModel = (options: UseAccountOrdersOptions = {}) => {
    const query = useAccountOrdersQuery(options);
    const expandedOrderIds = ref<string[]>([]);

    watch(query.orders, () => {
        expandedOrderIds.value = [];
    });

    const filteredOrders = computed<AccountOrder[]>(() => query.orders.value);
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

    const toggleDetails = (orderId: string): void => {
        if (isExpanded(orderId)) {
            expandedOrderIds.value = expandedOrderIds.value.filter((id) => id !== orderId);
            return;
        }

        expandedOrderIds.value = [...expandedOrderIds.value, orderId];
    };

    const totalItems = (order: AccountOrder): number =>
        order.items.reduce((sum, item) => sum + item.quantity, 0);

    const formatPrice = (value: number, currency = "USD"): string => formatMoney(value, currency);
    const formatDate = (value: string | null): string => formatOrderDate(value, "Unknown date");
    const formatAddress = (address: AccountOrderAddress | null): string =>
        formatOrderAddress(address);
    const orderStatusClass = (status: string): string => resolveOrderStatusClass(status);
    const paymentStatusClass = (status: string): string => resolvePaymentStatusClass(status);
    const shipmentStatusClass = (status: string): string => resolveShipmentStatusClass(status);

    return {
        orders: query.orders,
        filteredOrders,
        expandedOrderIds,
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
        toggleDetails,
        totalItems,
        formatPrice,
        formatDate,
        formatAddress,
        orderStatusClass,
        paymentStatusClass,
        shipmentStatusClass,
    };
};
