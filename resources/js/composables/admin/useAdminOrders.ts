import { computed, reactive, ref } from "vue";

import { getAdminOrderDetail, listAdminOrders, updateAdminOrderStatus } from "@/api/admin/orders";
import type { ListResponse } from "@/api/response";
import { useAdminMutation } from "@/composables/useAdminMutation";
import { useAdminNotice } from "@/composables/useAdminNotice";
import { useServerPaginatedList } from "@/composables/useServerPaginatedList";
import { buildAdminOrderListParams, type AdminOrderFilters } from "@/queries/admin/orders";
import type {
    AdminOrderDetail,
    AdminOrderListParams,
    AdminOrderSummary,
} from "@/types/admin-orders";

interface StatusDraft {
    status: string;
    payment_status: string;
    shipment_status: string;
    saving: boolean;
}

export const useAdminOrders = () => {
    const filters = reactive<AdminOrderFilters>({
        search: "",
        orderStatus: "all",
        paymentStatus: "all",
        shipmentStatus: "all",
    });
    const { notice, clearNotice, showSuccess, showApiError } = useAdminNotice();
    const { executeMutation } = useAdminMutation({
        clearNotice,
        showApiError,
    });
    const selectedOrderId = ref<string | null>(null);
    const isDetailLoading = ref(false);
    const orderDetails = reactive<Record<string, AdminOrderDetail>>({});
    const selectedOrderDetail = ref<AdminOrderDetail | null>(null);
    const statusDrafts = reactive<Record<string, StatusDraft>>({});

    const filteredOrders = computed<AdminOrderSummary[]>(() => orders.value);

    const selectedOrderSummary = computed<AdminOrderSummary | null>(() => {
        if (!selectedOrderId.value) {
            return null;
        }

        return orders.value.find((order) => order.id === selectedOrderId.value) ?? null;
    });

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

    const currentDraft = computed<StatusDraft>(() => {
        if (!selectedOrderDetail.value) {
            return {
                status: "pending",
                payment_status: "pending",
                shipment_status: "pending",
                saving: false,
            };
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
                showApiError(error, "Unable to load order details.");
            },
        });
    };

    const {
        items: orders,
        page,
        isLoading,
        meta,
        load: loadOrders,
    } = useServerPaginatedList<AdminOrderSummary, AdminOrderListParams>({
        buildParams: (targetPage) => buildAdminOrderListParams(targetPage, filters),
        fetchPage: listAdminOrders,
        filterSource: () => [
            filters.search,
            filters.orderStatus,
            filters.paymentStatus,
            filters.shipmentStatus,
        ],
        debounceMs: 300,
        resetOnError: true,
        onLoading: () => {
            clearNotice();
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
            showApiError(error, "Unable to load orders.");
        },
    });

    const selectOrder = async (orderId: string): Promise<void> => {
        selectedOrderId.value = orderId;
        await loadOrderDetail(orderId);
    };

    const updateSelectedOrderStatus = async (): Promise<void> => {
        if (!selectedOrderDetail.value) {
            return;
        }

        const order = selectedOrderDetail.value;
        const draft = ensureDraft(order);

        await executeMutation<AdminOrderDetail | null>({
            setPending: (pending) => {
                draft.saving = pending;
            },
            errorMessage: "Unable to update order statuses.",
            run: async () =>
                updateAdminOrderStatus(order.id, {
                    status: draft.status,
                    payment_status: draft.payment_status,
                    shipment_status: draft.shipment_status,
                }),
            onSuccess: (updatedOrder) => {
                if (updatedOrder) {
                    orderDetails[updatedOrder.id] = updatedOrder;
                    selectedOrderDetail.value = updatedOrder;
                    syncDraftWithOrder(updatedOrder);
                    orders.value = orders.value.map((item) =>
                        item.id === updatedOrder.id
                            ? {
                                  ...item,
                                  status: updatedOrder.status,
                                  payment_status: updatedOrder.payment_status,
                                  shipment_status: updatedOrder.shipment_status,
                                  total: updatedOrder.total,
                              }
                            : item,
                    );
                }

                showSuccess("Order statuses updated.");
            },
        });
    };

    const formatPrice = (value: number, currency = "USD"): string => {
        return new Intl.NumberFormat("en-US", {
            style: "currency",
            currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number(value ?? 0));
    };

    const formatAddress = (address: AdminOrderDetail["billing_address"]): string => {
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

    return {
        orders,
        selectedOrderId,
        isLoading,
        isDetailLoading,
        page,
        meta,
        filters,
        notice,
        filteredOrders,
        selectedOrderSummary,
        selectedOrderDetail,
        currentDraft,
        paidCount,
        completedCount,
        pendingPaymentCount,
        loadOrders,
        selectOrder,
        updateSelectedOrderStatus,
        formatPrice,
        formatAddress,
        orderStatusClass,
        paymentStatusClass,
        shipmentStatusClass,
    };
};
