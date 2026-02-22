import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope, nextTick } from "vue";
import { createPinia, setActivePinia } from "pinia";

import type { ListResponse } from "@/api/response";
import { useAdminCategories } from "@/composables/admin/useAdminCategories";
import { useAdminOrders } from "@/composables/admin/useAdminOrders";
import { useAdminPromotions } from "@/composables/admin/useAdminPromotions";
import type { AdminCategory } from "@/types/admin-categories";
import type { AdminOrderDetail, AdminOrderSummary } from "@/types/admin-orders";
import type { Promotion } from "@/types/admin-promotions";

vi.mock("@/api/admin/orders", () => ({
    listAdminOrders: vi.fn(),
    updateAdminOrderStatus: vi.fn(),
    getAdminOrderDetail: vi.fn(),
}));

vi.mock("@/api/admin/promotions", () => ({
    listPromotions: vi.fn(),
    createPromotion: vi.fn(),
    updatePromotion: vi.fn(),
    deletePromotion: vi.fn(),
    createPromotionCoupon: vi.fn(),
    updateCoupon: vi.fn(),
}));

vi.mock("@/api/admin/categories", () => ({
    listAdminCategories: vi.fn(),
    createAdminCategory: vi.fn(),
    updateAdminCategory: vi.fn(),
    deleteAdminCategory: vi.fn(),
}));

import { listAdminCategories } from "@/api/admin/categories";
import { getAdminOrderDetail, listAdminOrders } from "@/api/admin/orders";
import { listPromotions } from "@/api/admin/promotions";

const listAdminOrdersMock = listAdminOrders as unknown as ReturnType<typeof vi.fn>;
const getAdminOrderDetailMock = getAdminOrderDetail as unknown as ReturnType<typeof vi.fn>;
const listPromotionsMock = listPromotions as unknown as ReturnType<typeof vi.fn>;
const listAdminCategoriesMock = listAdminCategories as unknown as ReturnType<typeof vi.fn>;

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

const buildPromotion = (id: number): Promotion => ({
    id,
    name: `Campaign ${id}`,
    code: `CODE-${id}`,
    type: "percent",
    value: 10,
    is_active: true,
    usage_limit: null,
    usage_count: 0,
    starts_at: null,
    ends_at: null,
    coupons: [],
});

const buildCategory = (id: number): AdminCategory => ({
    id,
    parent_id: null,
    name: `Category ${id}`,
    slug: `category-${id}`,
    description: null,
    meta_title: null,
    meta_description: null,
    is_active: true,
    sort_order: id,
    parent: null,
    children_count: 0,
    products_count: 0,
});

const buildListResponse = <TItem>(items: TItem[], page: number): ListResponse<TItem> => ({
    data: items,
    meta: {
        current_page: page,
        last_page: 5,
        per_page: 30,
        total: 150,
    },
});

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
});

describe("admin server-driven list flows", () => {
    it("reloads orders from first page when filters change", async () => {
        listAdminOrdersMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);

            return buildListResponse([buildOrderSummary(`order-${page}`)], page);
        });
        getAdminOrderDetailMock.mockImplementation(async (orderId: string) =>
            buildOrderDetail(orderId),
        );

        const scope = effectScope();
        const api = scope.run(() => useAdminOrders());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadOrders(3);

        expect(listAdminOrdersMock).toHaveBeenLastCalledWith({
            page: 3,
        });
        expect(getAdminOrderDetailMock).toHaveBeenCalledWith("order-3");

        api.filters.search = "  jane@example.com  ";
        await nextTick();
        api.filters.orderStatus = "completed";
        await nextTick();
        api.filters.paymentStatus = "captured";
        await nextTick();
        api.filters.shipmentStatus = "delivered";
        await nextTick();

        await vi.advanceTimersByTimeAsync(300);

        expect(listAdminOrdersMock).toHaveBeenLastCalledWith({
            page: 1,
            q: "jane@example.com",
            status: "completed",
            payment_status: "captured",
            shipment_status: "delivered",
        });
        expect(api.page.value).toBe(1);

        scope.stop();
    });

    it("reloads promotions from first page when search/status filters change", async () => {
        listPromotionsMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);

            return buildListResponse([buildPromotion(page)], page);
        });

        const scope = effectScope();
        const api = scope.run(() => useAdminPromotions());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadPromotions(4);

        expect(listPromotionsMock).toHaveBeenLastCalledWith({
            page: 4,
        });

        api.searchQuery.value = "  vip  ";
        await nextTick();
        api.statusFilter.value = "active";
        await nextTick();

        await vi.advanceTimersByTimeAsync(300);

        expect(listPromotionsMock).toHaveBeenLastCalledWith({
            page: 1,
            q: "vip",
            is_active: true,
        });
        expect(api.page.value).toBe(1);

        scope.stop();
    });

    it("reloads categories from first page and keeps fixed page size", async () => {
        listAdminCategoriesMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);

            return buildListResponse([buildCategory(page)], page);
        });

        const scope = effectScope();
        const api = scope.run(() => useAdminCategories());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadCategories(2);

        expect(listAdminCategoriesMock).toHaveBeenLastCalledWith({
            page: 2,
            per_page: 200,
        });

        api.searchQuery.value = "  shoes  ";
        await nextTick();
        api.statusFilter.value = "inactive";
        await nextTick();

        await vi.advanceTimersByTimeAsync(300);

        expect(listAdminCategoriesMock).toHaveBeenLastCalledWith({
            page: 1,
            per_page: 200,
            q: "shoes",
            is_active: false,
        });
        expect(api.page.value).toBe(1);

        scope.stop();
    });
});
