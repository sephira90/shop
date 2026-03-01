import { onScopeDispose, reactive } from "vue";

import { getAccountOrderDetail } from "@/api/account/orders";
import { useApiError } from "@/composables/useApiError";
import { isAbortLikeError } from "@/composables/requestError";
import type { AccountOrderDetail } from "@/types/account-orders";

export const useAccountOrderDetailsState = () => {
    const { parseApiError } = useApiError();
    const orderDetails = reactive<Record<string, AccountOrderDetail>>({});
    const detailErrors = reactive<Record<string, string>>({});
    const detailLoading = reactive<Record<string, boolean>>({});
    const detailRequestIds = reactive<Record<string, number>>({});
    const detailAbortControllers = new Map<string, AbortController>();

    const cancelAllDetailRequests = (): void => {
        detailAbortControllers.forEach((controller) => controller.abort());
        detailAbortControllers.clear();

        Object.keys(detailLoading).forEach((orderId) => {
            detailLoading[orderId] = false;
        });
    };

    const resetDetails = (): void => {
        cancelAllDetailRequests();

        Object.keys(orderDetails).forEach((orderId) => {
            delete orderDetails[orderId];
        });
        Object.keys(detailErrors).forEach((orderId) => {
            delete detailErrors[orderId];
        });
        Object.keys(detailLoading).forEach((orderId) => {
            delete detailLoading[orderId];
        });
        Object.keys(detailRequestIds).forEach((orderId) => {
            delete detailRequestIds[orderId];
        });
    };

    const loadOrderDetail = async (orderId: string, force = false): Promise<void> => {
        if (!force && orderDetails[orderId]) {
            return;
        }

        const requestId = (detailRequestIds[orderId] ?? 0) + 1;
        detailRequestIds[orderId] = requestId;
        detailErrors[orderId] = "";
        detailAbortControllers.get(orderId)?.abort();

        const abortController =
            typeof AbortController === "undefined" ? null : new AbortController();

        if (abortController) {
            detailAbortControllers.set(orderId, abortController);
        }

        detailLoading[orderId] = true;

        try {
            const detail = await getAccountOrderDetail(orderId, {
                signal: abortController?.signal,
            });

            if (detailRequestIds[orderId] !== requestId) {
                return;
            }

            if (detail === null) {
                delete orderDetails[orderId];
                detailErrors[orderId] = "Unable to load order details.";

                return;
            }

            orderDetails[orderId] = detail;
        } catch (error: unknown) {
            if (detailRequestIds[orderId] !== requestId || isAbortLikeError(error)) {
                return;
            }

            detailErrors[orderId] = parseApiError(error, "Unable to load order details.");
            delete orderDetails[orderId];
        } finally {
            if (detailRequestIds[orderId] === requestId) {
                detailLoading[orderId] = false;
                detailAbortControllers.delete(orderId);
            }
        }
    };

    const isDetailLoading = (orderId: string): boolean => detailLoading[orderId] === true;
    const getOrderDetail = (orderId: string): AccountOrderDetail | null =>
        orderDetails[orderId] ?? null;
    const getDetailError = (orderId: string): string => detailErrors[orderId] ?? "";

    onScopeDispose(() => {
        cancelAllDetailRequests();
    });

    return {
        orderDetails,
        loadOrderDetail,
        cancelAllDetailRequests,
        resetDetails,
        isDetailLoading,
        getOrderDetail,
        getDetailError,
    };
};
