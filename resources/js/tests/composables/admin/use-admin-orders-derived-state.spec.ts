import { describe, expect, it } from "vitest";
import { ref } from "vue";

import { useAdminOrdersDerivedState } from "@/composables/admin/orders/useAdminOrdersDerivedState";
import type { AdminOrderSummary } from "@/types/admin-orders";

const buildOrderSummary = (
    id: string,
    status: AdminOrderSummary["status"] = "pending",
    paymentStatus: AdminOrderSummary["payment_status"] = "pending",
): AdminOrderSummary => ({
    id,
    order_number: `ORD-${id}`,
    email: "buyer@example.com",
    status,
    payment_status: paymentStatus,
    shipment_status: "pending",
    currency: "USD",
    total: 99,
    placed_at: "2026-02-22T00:00:00Z",
    created_at: "2026-02-22T00:00:00Z",
});

describe("useAdminOrdersDerivedState", () => {
    it("calculates selected summary and status metrics deterministically", () => {
        const orders = ref<AdminOrderSummary[]>([
            buildOrderSummary("order-1", "paid", "captured"),
            buildOrderSummary("order-2", "completed", "captured"),
            buildOrderSummary("order-3", "pending", "pending"),
        ]);
        const selectedOrderId = ref<string | null>("order-2");

        const state = useAdminOrdersDerivedState({
            orders,
            selectedOrderId,
        });

        expect(state.filteredOrders.value).toEqual(orders.value);
        expect(state.selectedOrderSummary.value?.id).toBe("order-2");
        expect(state.paidCount.value).toBe(2);
        expect(state.completedCount.value).toBe(1);
        expect(state.pendingPaymentCount.value).toBe(1);
    });

    it("returns null selected summary when selected id is absent", () => {
        const orders = ref<AdminOrderSummary[]>([buildOrderSummary("order-1")]);
        const selectedOrderId = ref<string | null>("missing");

        const state = useAdminOrdersDerivedState({
            orders,
            selectedOrderId,
        });

        expect(state.selectedOrderSummary.value).toBeNull();
    });
});
