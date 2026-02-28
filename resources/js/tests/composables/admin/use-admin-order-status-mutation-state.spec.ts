import { describe, expect, it, vi } from "vitest";
import { ref } from "vue";

import { applyUpdatedOrderStatusMutation } from "@/composables/admin/orders/adminOrderStatusMutationState";
import type { AdminOrderDetail, AdminOrderSummary } from "@/types/admin-orders";

const buildSummary = (id: string): AdminOrderSummary => ({
    id,
    order_number: `ORD-${id}`,
    email: "buyer@example.com",
    status: "pending",
    payment_status: "pending",
    shipment_status: "pending",
    currency: "USD",
    total: 100,
    placed_at: "2026-02-28T00:00:00Z",
    created_at: "2026-02-28T00:00:00Z",
});

const buildDetail = (id: string): AdminOrderDetail => ({
    ...buildSummary(id),
    subtotal: 80,
    billing_address: null,
    shipping_address: null,
    items: [],
});

describe("applyUpdatedOrderStatusMutation", () => {
    it("synchronizes detail cache, selected detail, drafts, and list projection", () => {
        const selectedOrderDetail = ref<AdminOrderDetail | null>(null);
        const orders = ref<AdminOrderSummary[]>([buildSummary("1"), buildSummary("2")]);
        const orderDetails: Record<string, AdminOrderDetail> = {
            "1": buildDetail("1"),
        };
        const syncDraftWithOrder = vi.fn();
        const updatedOrder: AdminOrderDetail = {
            ...buildDetail("2"),
            status: "completed",
            payment_status: "captured",
            shipment_status: "delivered",
            total: 120,
        };

        applyUpdatedOrderStatusMutation(
            {
                orderDetails,
                selectedOrderDetail,
                orders,
                syncDraftWithOrder,
            },
            updatedOrder,
        );

        expect(orderDetails["2"]).toEqual(updatedOrder);
        expect(selectedOrderDetail.value).toEqual(updatedOrder);
        expect(syncDraftWithOrder).toHaveBeenCalledWith(updatedOrder);
        expect(orders.value).toEqual([
            buildSummary("1"),
            {
                ...buildSummary("2"),
                status: "completed",
                payment_status: "captured",
                shipment_status: "delivered",
                total: 120,
            },
        ]);
    });
});
