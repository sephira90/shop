import { updateAdminOrderStatus } from "@/api/admin/orders";
import { executeAdminActionMutationPipeline } from "@/composables/admin/adminActionMutationPipeline";
import type { AdminSuccessNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { AdminOrderDetail } from "@/types/admin-orders";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

import { applyUpdatedOrderStatusMutation } from "./adminOrderStatusMutationState";
import type { useAdminOrdersQuery } from "./useAdminOrdersQuery";

interface UseAdminOrdersMutationsOptions {
    query: ReturnType<typeof useAdminOrdersQuery>;
    executeMutation: ExecuteAdminMutation;
    showSuccess: AdminSuccessNoticeAdapter["showSuccess"];
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

        await executeAdminActionMutationPipeline<AdminOrderDetail | null>({
            executeMutation,
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
            resolveSuccessMessage: () => "Order statuses updated.",
            showSuccess,
            afterSuccess: (updatedOrder) => {
                if (updatedOrder) {
                    applyUpdatedOrderStatusMutation(
                        {
                            orderDetails: query.orderDetails,
                            selectedOrderDetail: query.selectedOrderDetail,
                            orders: query.orders,
                            syncDraftWithOrder: query.syncDraftWithOrder,
                        },
                        updatedOrder,
                    );
                }
            },
        });
    };

    return {
        updateSelectedOrderStatus,
    };
};
