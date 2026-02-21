import { computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

import { listAccountOrders } from "@/api/account/orders";
import { useApiError } from "@/composables/useApiError";
import { usePaginationMeta } from "@/composables/usePaginationMeta";
import {
    buildAccountOrdersListParams,
    buildAccountOrdersRouteQuery,
    isSameAccountOrdersRouteQuery,
    parseAccountOrdersFiltersFromRouteQuery,
} from "@/queries/account-orders";
import type {
    AccountOrder,
    AccountOrderAddress,
    AccountOrderStatusFilter,
} from "@/types/account-orders";

export const useAccountOrders = () => {
    const route = useRoute();
    const router = useRouter();
    const { parseApiError } = useApiError();
    const { meta, applyMeta, resetMeta } = usePaginationMeta();
    const initialFilters = parseAccountOrdersFiltersFromRouteQuery(route.query);
    const orders = ref<AccountOrder[]>([]);
    const expandedOrderIds = ref<string[]>([]);
    const isLoading = ref(false);
    const loadError = ref("");
    const searchQuery = ref(initialFilters.searchQuery);
    const statusFilter = ref<AccountOrderStatusFilter>(initialFilters.statusFilter);
    const page = ref(initialFilters.page);
    let activeRequestId = 0;

    const filteredOrders = computed<AccountOrder[]>(() => orders.value);

    const loadedTotal = computed<number>(() =>
        orders.value.reduce((sum, order) => sum + Number(order.total ?? 0), 0),
    );
    const paidCount = computed<number>(
        () =>
            orders.value.filter(
                (order) => order.status === "paid" || order.payment_status === "captured",
            ).length,
    );
    const shipmentActiveCount = computed<number>(
        () =>
            orders.value.filter((order) => ["packed", "shipped"].includes(order.shipment_status))
                .length,
    );

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
            expandedOrderIds.value = [];
        } catch (error: unknown) {
            if (requestId !== activeRequestId) {
                return;
            }

            loadError.value = parseApiError(error, "Unable to load orders.");
            orders.value = [];
            resetMeta();
            page.value = 1;
            expandedOrderIds.value = [];
        } finally {
            if (requestId === activeRequestId) {
                isLoading.value = false;
            }
        }
    };

    const syncRouteState = async (targetPage = page.value): Promise<boolean> => {
        const nextQuery = buildAccountOrdersRouteQuery({
            searchQuery: searchQuery.value,
            statusFilter: statusFilter.value,
            page: targetPage,
        });

        if (isSameAccountOrdersRouteQuery(route.query, nextQuery)) {
            return false;
        }

        await router.replace({
            query: nextQuery,
        });

        return true;
    };

    const loadOrders = async (targetPage = page.value): Promise<void> => {
        const routeWasUpdated = await syncRouteState(targetPage);

        if (!routeWasUpdated) {
            await fetchOrders(targetPage);
        }
    };

    const applyFilters = async (): Promise<void> => {
        await loadOrders(1);
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

    const formatPrice = (value: number, currency = "USD"): string => {
        return new Intl.NumberFormat("en-US", {
            style: "currency",
            currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number(value ?? 0));
    };

    const formatDate = (value: string | null): string => {
        if (!value) {
            return "Unknown date";
        }

        return new Intl.DateTimeFormat("en-US", {
            dateStyle: "medium",
            timeStyle: "short",
        }).format(new Date(value));
    };

    const formatAddress = (address: AccountOrderAddress | null): string => {
        if (!address) {
            return "Not provided";
        }

        return (
            [address.line1, address.city, address.country, address.postcode]
                .filter(Boolean)
                .join(", ") || "Not provided"
        );
    };

    const orderStatusClass = (status: string): string => {
        return (
            {
                pending: "status-chip--warn",
                paid: "status-chip--good",
                processing: "status-chip--info",
                shipped: "status-chip--info",
                completed: "status-chip--good",
                cancelled: "status-chip--bad",
                refunded: "status-chip--neutral",
            }[status] ?? "status-chip--neutral"
        );
    };

    const paymentStatusClass = (status: string): string => {
        return (
            {
                pending: "status-chip--warn",
                authorized: "status-chip--info",
                captured: "status-chip--good",
                failed: "status-chip--bad",
                refunded: "status-chip--neutral",
            }[status] ?? "status-chip--neutral"
        );
    };

    const shipmentStatusClass = (status: string): string => {
        return (
            {
                pending: "status-chip--warn",
                packed: "status-chip--info",
                shipped: "status-chip--info",
                delivered: "status-chip--good",
                returned: "status-chip--bad",
            }[status] ?? "status-chip--neutral"
        );
    };

    watch(
        () => route.query,
        (query) => {
            const parsed = parseAccountOrdersFiltersFromRouteQuery(query);

            searchQuery.value = parsed.searchQuery;
            statusFilter.value = parsed.statusFilter;
            page.value = parsed.page;
            void fetchOrders(parsed.page);
        },
        { immediate: true },
    );

    return {
        orders,
        filteredOrders,
        expandedOrderIds,
        searchQuery,
        statusFilter,
        page,
        meta,
        isLoading,
        loadError,
        loadedTotal,
        paidCount,
        shipmentActiveCount,
        loadOrders,
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
