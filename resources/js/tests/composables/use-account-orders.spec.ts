import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope, reactive } from "vue";

import type { ListResponse } from "@/api/response";
import { useAccountOrders } from "@/composables/useAccountOrders";
import type { AccountOrder } from "@/types/account-orders";

vi.mock("@/api/account/orders", () => ({
    listAccountOrders: vi.fn(),
    getAccountOrdersSummary: vi.fn(),
}));

import { listAccountOrders } from "@/api/account/orders";

const listAccountOrdersMock = listAccountOrders as unknown as ReturnType<typeof vi.fn>;

const buildOrder = (id: string): AccountOrder => ({
    id,
    order_number: `ORD-${id}`,
    email: "buyer@example.com",
    status: "pending",
    payment_status: "pending",
    shipment_status: "packed",
    currency: "USD",
    total: 125,
    items: [
        {
            product_variant_id: 10,
            sku: `SKU-${id}`,
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
    placed_at: "2026-02-22T00:00:00Z",
    created_at: "2026-02-22T00:00:00Z",
});

const buildListResponse = (page: number): ListResponse<AccountOrder> => ({
    data: [buildOrder(String(page))],
    meta: {
        current_page: page,
        last_page: 5,
        per_page: 30,
        total: 150,
    },
});

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

    it("toggles expanded details by order id", async () => {
        listAccountOrdersMock.mockResolvedValue(buildListResponse(1));

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

        expect(api.isExpanded("1")).toBe(false);
        api.toggleDetails("1");
        expect(api.isExpanded("1")).toBe(true);
        api.toggleDetails("1");
        expect(api.isExpanded("1")).toBe(false);

        scope.stop();
    });
});
