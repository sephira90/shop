import type { Ref } from "vue";

import type { AdminOrderDetail, AdminOrderSummary } from "@/types/admin-orders";

interface AdminOrderStatusMutationState {
    orderDetails: Record<string, AdminOrderDetail>;
    selectedOrderDetail: Ref<AdminOrderDetail | null>;
    orders: Ref<AdminOrderSummary[]>;
    syncDraftWithOrder: (order: AdminOrderSummary | AdminOrderDetail) => void;
}

export const applyUpdatedOrderStatusMutation = (
    state: AdminOrderStatusMutationState,
    updatedOrder: AdminOrderDetail,
): void => {
    state.orderDetails[updatedOrder.id] = updatedOrder;
    state.selectedOrderDetail.value = updatedOrder;
    state.syncDraftWithOrder(updatedOrder);
    state.orders.value = state.orders.value.map((item) =>
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
};
