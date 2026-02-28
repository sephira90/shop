import { computed, onScopeDispose, reactive, ref } from "vue";

import { getAdminOrderDetail } from "@/api/admin/orders";
import { isAbortLikeError } from "@/composables/requestError";
import type { AdminOrderDetail, AdminOrderSummary } from "@/types/admin-orders";

interface AdminOrderDetailsNoticeAdapter {
    showApiError: (error: unknown, fallback: string) => void;
}

export interface StatusDraft {
    status: string;
    payment_status: string;
    shipment_status: string;
    saving: boolean;
}

const createDefaultStatusDraft = (): StatusDraft => ({
    status: "pending",
    payment_status: "pending",
    shipment_status: "pending",
    saving: false,
});

export const useAdminOrderDetailsState = (notice: AdminOrderDetailsNoticeAdapter) => {
    const selectedOrderId = ref<string | null>(null);
    const isDetailLoading = ref(false);
    const orderDetails = reactive<Record<string, AdminOrderDetail>>({});
    const selectedOrderDetail = ref<AdminOrderDetail | null>(null);
    const statusDrafts = reactive<Record<string, StatusDraft>>({});
    let activeDetailRequestId = 0;
    let activeDetailAbortController: AbortController | null = null;

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

    const cancelActiveDetailRequest = (): void => {
        activeDetailRequestId += 1;
        activeDetailAbortController?.abort();
        activeDetailAbortController = null;
        isDetailLoading.value = false;
    };

    const loadOrderDetail = async (orderId: string, force = false): Promise<void> => {
        if (!force && orderDetails[orderId]) {
            selectedOrderDetail.value = orderDetails[orderId];
            return;
        }

        const requestId = activeDetailRequestId + 1;
        activeDetailRequestId = requestId;
        activeDetailAbortController?.abort();
        const abortController =
            typeof AbortController === "undefined" ? null : new AbortController();
        activeDetailAbortController = abortController;
        isDetailLoading.value = true;

        try {
            const detail = await getAdminOrderDetail(orderId, {
                signal: abortController?.signal,
            });

            if (requestId !== activeDetailRequestId || selectedOrderId.value !== orderId) {
                return;
            }

            if (!detail) {
                selectedOrderDetail.value = null;
                return;
            }

            orderDetails[orderId] = detail;
            selectedOrderDetail.value = detail;
            syncDraftWithOrder(detail);
        } catch (error: unknown) {
            if (requestId !== activeDetailRequestId || isAbortLikeError(error)) {
                return;
            }

            if (selectedOrderId.value === orderId) {
                selectedOrderDetail.value = null;
            }

            notice.showApiError(error, "Unable to load order details.");
        } finally {
            if (requestId === activeDetailRequestId) {
                isDetailLoading.value = false;
                activeDetailAbortController = null;
            }
        }
    };

    const syncSelectionWithOrderList = async (
        orders: readonly AdminOrderSummary[],
    ): Promise<void> => {
        orders.forEach((order) => {
            syncDraftWithOrder(order);
        });

        if (!selectedOrderId.value || !orders.some((order) => order.id === selectedOrderId.value)) {
            selectedOrderId.value = orders[0]?.id ?? null;
        }

        if (selectedOrderId.value) {
            await loadOrderDetail(selectedOrderId.value);
            return;
        }

        selectedOrderDetail.value = null;
    };

    const resetOnOrderListError = (): void => {
        cancelActiveDetailRequest();
        selectedOrderId.value = null;
        selectedOrderDetail.value = null;
    };

    const selectOrder = async (orderId: string): Promise<void> => {
        selectedOrderId.value = orderId;
        await loadOrderDetail(orderId);
    };

    const currentDraft = computed<StatusDraft>(() => {
        if (!selectedOrderDetail.value) {
            return createDefaultStatusDraft();
        }

        return ensureDraft(selectedOrderDetail.value);
    });

    onScopeDispose(() => {
        cancelActiveDetailRequest();
    });

    return {
        selectedOrderId,
        isDetailLoading,
        orderDetails,
        selectedOrderDetail,
        statusDrafts,
        currentDraft,
        ensureDraft,
        syncDraftWithOrder,
        cancelActiveDetailRequest,
        loadOrderDetail,
        syncSelectionWithOrderList,
        resetOnOrderListError,
        selectOrder,
    };
};
