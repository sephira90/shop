import { updateAdminOrderStatus } from "@/api/admin/orders";
import type { AdminOrderDetail } from "@/types/admin-orders";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

import type { useAdminOrdersQuery } from "./useAdminOrdersQuery";

interface UseAdminOrdersMutationsOptions {
    query: ReturnType<typeof useAdminOrdersQuery>;
    executeMutation: ExecuteAdminMutation;
    showSuccess: (message: string) => void;
}

export const useAdminOrdersMutations = ({
    query,
    executeMutation,
    showSuccess,
}: UseAdminOrdersMutationsOptions) => {
    const updateSelectedOrderStatus = async (): Promise<void> => {
        if (!query.selectedOrderDetail.value) {
            return;
        }

        const order = query.selectedOrderDetail.value;
        const draft = query.ensureDraft(order);

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
                    query.orderDetails[updatedOrder.id] = updatedOrder;
                    query.selectedOrderDetail.value = updatedOrder;
                    query.syncDraftWithOrder(updatedOrder);
                    query.orders.value = query.orders.value.map((item) =>
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

    return {
        updateSelectedOrderStatus,
    };
};
