import { computed, type Ref } from "vue";

import type { AdminOrderSummary } from "@/types/admin-orders";

interface UseAdminOrdersDerivedStateOptions {
    orders: Ref<AdminOrderSummary[]>;
    selectedOrderId: Ref<string | null>;
}

export const useAdminOrdersDerivedState = ({
    orders,
    selectedOrderId,
}: UseAdminOrdersDerivedStateOptions) => {
    const filteredOrders = computed<AdminOrderSummary[]>(() => orders.value);

    const selectedOrderSummary = computed<AdminOrderSummary | null>(() => {
        if (!selectedOrderId.value) {
            return null;
        }

        return orders.value.find((order) => order.id === selectedOrderId.value) ?? null;
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

    return {
        filteredOrders,
        selectedOrderSummary,
        paidCount,
        completedCount,
        pendingPaymentCount,
    };
};
