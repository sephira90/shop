import { describe, expect, it, vi } from "vitest";
import { effectScope, reactive } from "vue";

import { useAdminOrdersListState } from "@/composables/admin/orders/useAdminOrdersListState";
import type { AdminOrderRouteFilters } from "@/queries/admin/orders";
import type { AdminOrderSummary } from "@/types/admin-orders";

vi.mock("@/api/admin/orders", () => ({
    listAdminOrders: vi.fn(),
}));

import { listAdminOrders } from "@/api/admin/orders";

const listAdminOrdersMock = listAdminOrders as unknown as ReturnType<typeof vi.fn>;

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

const createFilterState = () => {
    const filters = reactive({
        search: "",
        orderStatus: "all",
        paymentStatus: "all",
        shipmentStatus: "all",
    });

    return {
        initialPage: 1,
        filters,
        buildListParams: (targetPage: number) => {
            const params: {
                page: number;
                q?: string;
                status?: string;
                payment_status?: string;
                shipment_status?: string;
            } = {
                page: targetPage,
            };
            const query = filters.search.trim();

            if (query !== "") {
                params.q = query;
            }

            if (filters.orderStatus !== "all") {
                params.status = filters.orderStatus;
            }

            if (filters.paymentStatus !== "all") {
                params.payment_status = filters.paymentStatus;
            }

            if (filters.shipmentStatus !== "all") {
                params.shipment_status = filters.shipmentStatus;
            }

            return params;
        },
        filterSource: () =>
            [
                filters.search,
                filters.orderStatus,
                filters.paymentStatus,
                filters.shipmentStatus,
            ] as [string, string, string, string],
        applyParsedFilters: (parsed: AdminOrderRouteFilters) => {
            filters.search = parsed.search;
            filters.orderStatus = parsed.orderStatus;
            filters.paymentStatus = parsed.paymentStatus;
            filters.shipmentStatus = parsed.shipmentStatus;

            return parsed.page;
        },
        readFiltersForPage: (targetPage: number) => ({
            search: filters.search,
            orderStatus: filters.orderStatus,
            paymentStatus: filters.paymentStatus,
            shipmentStatus: filters.shipmentStatus,
            page: targetPage,
        }),
    };
};

describe("useAdminOrdersListState", () => {
    it("loads list payload and syncs detail selection callback", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        const syncSelectionWithOrderList = vi.fn(async () => {});
        const resetOnOrderListError = vi.fn();
        listAdminOrdersMock.mockResolvedValue({
            data: [buildOrderSummary("order-2")],
            meta: {
                current_page: 2,
                last_page: 5,
                per_page: 30,
                total: 150,
            },
        });

        const scope = effectScope();
        const api = scope.run(() => {
            const filterState = createFilterState();
            filterState.filters.search = "  vip  ";
            filterState.filters.orderStatus = "completed";

            return useAdminOrdersListState({
                notice: {
                    clearNotice,
                    showApiError,
                },
                filterState,
                detailState: {
                    syncSelectionWithOrderList,
                    resetOnOrderListError,
                },
            });
        });

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadOrders(2);

        expect(listAdminOrdersMock).toHaveBeenCalledWith({
            page: 2,
            q: "vip",
            status: "completed",
        });
        expect(clearNotice).toHaveBeenCalledTimes(1);
        expect(syncSelectionWithOrderList).toHaveBeenCalledWith([buildOrderSummary("order-2")]);
        expect(api.orders.value).toEqual([buildOrderSummary("order-2")]);
        expect(api.page.value).toBe(2);
        expect(showApiError).not.toHaveBeenCalled();
        expect(resetOnOrderListError).not.toHaveBeenCalled();

        scope.stop();
    });

    it("resets dependent detail state and reports notice on list failure", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        const syncSelectionWithOrderList = vi.fn(async () => {});
        const resetOnOrderListError = vi.fn();
        listAdminOrdersMock.mockRejectedValue(new Error("Failed to load"));

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminOrdersListState({
                notice: {
                    clearNotice,
                    showApiError,
                },
                filterState: createFilterState(),
                detailState: {
                    syncSelectionWithOrderList,
                    resetOnOrderListError,
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadOrders(3);

        expect(resetOnOrderListError).toHaveBeenCalledTimes(1);
        expect(syncSelectionWithOrderList).not.toHaveBeenCalled();
        expect(showApiError).toHaveBeenCalledTimes(1);
        expect(api.orders.value).toEqual([]);
        expect(api.page.value).toBe(1);

        scope.stop();
    });
});
