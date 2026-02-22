import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import { useAdminMutation } from "@/composables/useAdminMutation";
import { useAdminNotice } from "@/composables/useAdminNotice";
import {
    formatMoney,
    formatOrderAddress,
    type OrderAddressLike,
    orderStatusClass as resolveOrderStatusClass,
    paymentStatusClass as resolvePaymentStatusClass,
    shipmentStatusClass as resolveShipmentStatusClass,
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
    const orderStatusClass = (status: string): string => resolveOrderStatusClass(status);
    const paymentStatusClass = (status: string): string => resolvePaymentStatusClass(status);
    const shipmentStatusClass = (status: string): string => resolveShipmentStatusClass(status);

    return {
        notice,
        ...query,
        ...mutations,
        formatPrice,
        formatAddress,
        orderStatusClass,
        paymentStatusClass,
        shipmentStatusClass,
    };
};
