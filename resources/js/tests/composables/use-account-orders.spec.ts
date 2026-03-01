import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope, reactive } from "vue";

import type { ListResponse } from "@/api/response";
import { useAccountOrders } from "@/composables/useAccountOrders";
import type { AccountOrderDetail, AccountOrderSummary } from "@/types/account-orders";

vi.mock("@/api/account/orders", () => ({
    listAccountOrders: vi.fn(),
    getAccountOrderDetail: vi.fn(),
    getAccountOrdersSummary: vi.fn(),
}));

import { getAccountOrderDetail, listAccountOrders } from "@/api/account/orders";

const listAccountOrdersMock = listAccountOrders as unknown as ReturnType<typeof vi.fn>;
const getAccountOrderDetailMock = getAccountOrderDetail as unknown as ReturnType<typeof vi.fn>;

const buildSummaryOrder = (id: string): AccountOrderSummary => ({
    id,
    order_number: `ORD-${id}`,
    email: "buyer@example.com",
    status: "pending",
    payment_status: "pending",
    shipment_status: "packed",
    currency: "USD",
    total: 125,
    placed_at: "2026-02-22T00:00:00Z",
    created_at: "2026-02-22T00:00:00Z",
});

const buildDetailOrder = (id: string, sku: string): AccountOrderDetail => ({
    ...buildSummaryOrder(id),
    subtotal: 125,
    discount_total: 0,
    shipping_total: 0,
    items: [
        {
            product_variant_id: 10,
            sku,
            name: `Item ${id}`,
            quantity: 2,
            unit_price: 62.5,
            total_price: 125,
        },
    ],
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
    payments: [],
    shipments: [],
});

const buildListResponse = (page: number): ListResponse<AccountOrderSummary> => ({
    data: [buildSummaryOrder(String(page))],
    meta: {
        current_page: page,
        last_page: 5,
        per_page: 30,
        total: 150,
    },
});

const createDeferred = <T>() => {
    let resolve!: (value: T) => void;
    const promise = new Promise<T>((innerResolve) => {
        resolve = innerResolve;
    });

    return {
        promise,
        resolve,
    };
};

beforeEach(() => {
    vi.clearAllMocks();
});

describe("useAccountOrders", () => {
    it("loads from route query and keeps route in sync when filters apply", async () => {
        listAccountOrdersMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);
            return buildListResponse(page);
        });

        const route = reactive<{ query: Record<string, unknown> }>({
            query: {
                q: "  ORD-2  ",
                status: "completed",
                page: "2",
            },
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });

        const scope = effectScope();
        const api = scope.run(() => useAccountOrders({ route, router: { replace } }));

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await vi.waitFor(() => {
            expect(listAccountOrdersMock).toHaveBeenCalledTimes(1);
        });
        await vi.waitFor(() => {
            expect(api.orders.value).toHaveLength(1);
        });

        expect(listAccountOrdersMock).toHaveBeenLastCalledWith({
            page: 2,
            q: "ORD-2",
            status: "completed",
        });
        expect(api.page.value).toBe(2);
        expect(api.orders.value).toHaveLength(1);
        expect(api.loadedTotal.value).toBe(125);
        expect(api.paidCount.value).toBe(0);
        expect(api.shipmentActiveCount.value).toBe(1);

        api.searchQuery.value = "  jane@example.com  ";
        await api.applyFilters();

        expect(replace).toHaveBeenCalledWith({
            query: {
                q: "jane@example.com",
                status: "completed",
            },
        });

        await vi.waitFor(() => {
            expect(listAccountOrdersMock).toHaveBeenCalledTimes(2);
        });

        expect(listAccountOrdersMock).toHaveBeenLastCalledWith({
            page: 1,
            q: "jane@example.com",
            status: "completed",
        });
        expect(api.page.value).toBe(1);

        scope.stop();
    });

    it("loads order details lazily when a card is expanded", async () => {
        listAccountOrdersMock.mockResolvedValue(buildListResponse(1));
        getAccountOrderDetailMock.mockResolvedValue(buildDetailOrder("1", "SKU-1"));

        const route = reactive<{ query: Record<string, unknown> }>({
            query: {},
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });

        const scope = effectScope();
        const api = scope.run(() => useAccountOrders({ route, router: { replace } }));

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await vi.waitFor(() => {
            expect(listAccountOrdersMock).toHaveBeenCalledTimes(1);
        });
        await vi.waitFor(() => {
            expect(api.orders.value).toHaveLength(1);
        });

        expect(api.isExpanded("1")).toBe(false);
        expect(api.getOrderDetail("1")).toBeNull();

        await api.toggleDetails("1");

        expect(api.isExpanded("1")).toBe(true);
        expect(getAccountOrderDetailMock).toHaveBeenCalledWith("1", { signal: expect.anything() });
        await vi.waitFor(() => {
            expect(api.getOrderDetail("1")?.items[0]?.sku).toBe("SKU-1");
        });
        expect(api.totalItems(api.getOrderDetail("1"))).toBe(2);

        await api.toggleDetails("1");

        expect(api.isExpanded("1")).toBe(false);

        scope.stop();
    });

    it("ignores stale detail responses for the same order", async () => {
        listAccountOrdersMock.mockResolvedValue(buildListResponse(1));

        const firstDetail = createDeferred<AccountOrderDetail>();
        const secondDetail = createDeferred<AccountOrderDetail>();

        getAccountOrderDetailMock
            .mockImplementationOnce(() => firstDetail.promise)
            .mockImplementationOnce(() => secondDetail.promise);

        const route = reactive<{ query: Record<string, unknown> }>({
            query: {},
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });

        const scope = effectScope();
        const api = scope.run(() => useAccountOrders({ route, router: { replace } }));

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await vi.waitFor(() => {
            expect(listAccountOrdersMock).toHaveBeenCalledTimes(1);
        });
        await vi.waitFor(() => {
            expect(api.orders.value).toHaveLength(1);
        });

        const firstLoad = api.toggleDetails("1");
        const secondLoad = api.loadOrderDetail("1", true);

        secondDetail.resolve(buildDetailOrder("1", "SKU-NEW"));
        await secondLoad;

        firstDetail.resolve(buildDetailOrder("1", "SKU-OLD"));
        await firstLoad;

        await vi.waitFor(() => {
            expect(api.getOrderDetail("1")?.items[0]?.sku).toBe("SKU-NEW");
        });

        scope.stop();
    });
});
