import { describe, expect, it, vi } from "vitest";
import { effectScope } from "vue";

import { useAdminOrderDetailsState } from "@/composables/admin/orders/useAdminOrderDetailsState";
import type { AdminOrderDetail, AdminOrderSummary } from "@/types/admin-orders";

vi.mock("@/api/admin/orders", () => ({
    getAdminOrderDetail: vi.fn(),
}));

import { getAdminOrderDetail } from "@/api/admin/orders";

const getAdminOrderDetailMock = getAdminOrderDetail as unknown as ReturnType<typeof vi.fn>;

const buildOrderSummary = (id: string): AdminOrderSummary => ({
    id,
    order_number: `ORD-${id}`,
    email: "buyer@example.com",
    status: "pending",
    payment_status: "pending",
    shipment_status: "pending",
    currency: "USD",
    total: 99,
    placed_at: "2026-02-22T00:00:00Z",
    created_at: "2026-02-22T00:00:00Z",
});

const buildOrderDetail = (id: string): AdminOrderDetail => ({
    ...buildOrderSummary(id),
    subtotal: 99,
    billing_address: {
        line1: "Main st 1",
        city: "New York",
        country: "US",
        postcode: "10001",
    },
    shipping_address: {
        line1: "Main st 1",
        city: "New York",
        country: "US",
        postcode: "10001",
    },
    items: [],
});

const createDeferred = <TValue>() => {
    let resolve!: (value: TValue | PromiseLike<TValue>) => void;
    let reject!: (reason?: unknown) => void;
    const promise = new Promise<TValue>((promiseResolve, promiseReject) => {
        resolve = promiseResolve;
        reject = promiseReject;
    });

    return {
        promise,
        resolve,
        reject,
    };
};

describe("useAdminOrderDetailsState", () => {
    it("selects first order from list and hydrates its detail", async () => {
        const showApiError = vi.fn();
        getAdminOrderDetailMock.mockResolvedValue(buildOrderDetail("order-1"));

        const scope = effectScope();
        const state = scope.run(() =>
            useAdminOrderDetailsState({
                showApiError,
            }),
        );

        expect(state).not.toBeNull();
        if (!state) {
            scope.stop();
            return;
        }

        await state.syncSelectionWithOrderList([
            buildOrderSummary("order-1"),
            buildOrderSummary("order-2"),
        ]);

        expect(state.selectedOrderId.value).toBe("order-1");
        expect(state.selectedOrderDetail.value?.id).toBe("order-1");
        expect(state.currentDraft.value.status).toBe("pending");
        expect(getAdminOrderDetailMock).toHaveBeenCalledWith("order-1", expect.anything());
        expect(showApiError).not.toHaveBeenCalled();

        scope.stop();
    });

    it("ignores stale detail response after selecting another order", async () => {
        const showApiError = vi.fn();
        const orderOneDeferred = createDeferred<AdminOrderDetail | null>();
        const orderTwoDeferred = createDeferred<AdminOrderDetail | null>();
        getAdminOrderDetailMock.mockImplementation((orderId: string) => {
            if (orderId === "order-1") {
                return orderOneDeferred.promise;
            }

            return orderTwoDeferred.promise;
        });

        const scope = effectScope();
        const state = scope.run(() =>
            useAdminOrderDetailsState({
                showApiError,
            }),
        );

        expect(state).not.toBeNull();
        if (!state) {
            scope.stop();
            return;
        }

        const firstSync = state.syncSelectionWithOrderList([
            buildOrderSummary("order-1"),
            buildOrderSummary("order-2"),
        ]);
        await vi.waitFor(() => {
            expect(getAdminOrderDetailMock).toHaveBeenCalledWith("order-1", expect.anything());
        });

        const selectSecondOrder = state.selectOrder("order-2");
        await vi.waitFor(() => {
            expect(getAdminOrderDetailMock).toHaveBeenCalledWith("order-2", expect.anything());
        });

        orderTwoDeferred.resolve(buildOrderDetail("order-2"));
        await selectSecondOrder;

        orderOneDeferred.resolve(buildOrderDetail("order-1"));
        await firstSync;

        expect(state.selectedOrderId.value).toBe("order-2");
        expect(state.selectedOrderDetail.value?.id).toBe("order-2");
        expect(showApiError).not.toHaveBeenCalled();

        scope.stop();
    });
});
