import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import { useAdminMutation } from "@/composables/useAdminMutation";
import { useAdminNotice } from "@/composables/useAdminNotice";
import {
    formatMoney,
    formatOrderAddress,
    type OrderAddressLike,
    orderStatusTone as resolveOrderStatusTone,
    paymentStatusTone as resolvePaymentStatusTone,
    shipmentStatusTone as resolveShipmentStatusTone,
    type StatusTone,
} from "@/utils/order-presentation";

import { useAdminOrdersMutations } from "./useAdminOrdersMutations";
import { useAdminOrdersQuery } from "./useAdminOrdersQuery";

interface UseAdminOrdersOptions {
    routeSync?: AdminRouteSyncOptions;
}

export const useAdminOrdersViewModel = (options: UseAdminOrdersOptions = {}) => {
    const { notice, clearNotice, showSuccess, showApiError } = useAdminNotice();
    const { executeMutation } = useAdminMutation({
        clearNotice,
        showApiError,
    });
    const query = useAdminOrdersQuery({
        notice: {
            clearNotice,
            showApiError,
        },
        executeMutation,
        routeSync: options.routeSync,
    });
    const mutations = useAdminOrdersMutations({
        query,
        executeMutation,
        showSuccess,
    });

    const formatPrice = (value: number, currency = "USD"): string => formatMoney(value, currency);
    const formatAddress = (address: OrderAddressLike | null): string => formatOrderAddress(address);
    const orderStatusTone = (status: string): StatusTone => resolveOrderStatusTone(status);
    const paymentStatusTone = (status: string): StatusTone => resolvePaymentStatusTone(status);
    const shipmentStatusTone = (status: string): StatusTone => resolveShipmentStatusTone(status);

    return {
        notice,
        ...query,
        ...mutations,
        formatPrice,
        formatAddress,
        orderStatusTone,
        paymentStatusTone,
        shipmentStatusTone,
    };
};
