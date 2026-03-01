import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope, nextTick, reactive } from "vue";
import { createPinia, setActivePinia } from "pinia";

import type { ListResponse } from "@/api/response";
import { useAdminCategories } from "@/composables/admin/useAdminCategories";
import { useAdminOrders } from "@/composables/admin/useAdminOrders";
import { useAdminProducts } from "@/composables/admin/useAdminProducts";
import { useAdminPromotions } from "@/composables/admin/useAdminPromotions";
import type { AdminCategory } from "@/types/admin-categories";
import type { AdminOrderDetail, AdminOrderSummary } from "@/types/admin-orders";
import type { AdminProduct } from "@/types/admin-products";
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
    listAdminCategoryOptions: vi.fn(),
    listAdminCategories: vi.fn(),
    createAdminCategory: vi.fn(),
    updateAdminCategory: vi.fn(),
    deleteAdminCategory: vi.fn(),
}));

vi.mock("@/api/admin/products", () => ({
    listAdminProducts: vi.fn(),
    createAdminProduct: vi.fn(),
    updateAdminProduct: vi.fn(),
    deleteAdminProduct: vi.fn(),
    refreshAdminCatalogCache: vi.fn(),
}));

import { listAdminCategoryOptions, listAdminCategories } from "@/api/admin/categories";
import { getAdminOrderDetail, listAdminOrders } from "@/api/admin/orders";
import { listAdminProducts } from "@/api/admin/products";
import { listPromotions } from "@/api/admin/promotions";

const listAdminOrdersMock = listAdminOrders as unknown as ReturnType<typeof vi.fn>;
const getAdminOrderDetailMock = getAdminOrderDetail as unknown as ReturnType<typeof vi.fn>;
const listPromotionsMock = listPromotions as unknown as ReturnType<typeof vi.fn>;
const listAdminCategoryOptionsMock = listAdminCategoryOptions as unknown as ReturnType<
    typeof vi.fn
>;
const listAdminCategoriesMock = listAdminCategories as unknown as ReturnType<typeof vi.fn>;
const listAdminProductsMock = listAdminProducts as unknown as ReturnType<typeof vi.fn>;

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

const buildProduct = (id: number): AdminProduct => ({
    id,
    sku: `SKU-${id}`,
    name: `Product ${id}`,
    slug: `product-${id}`,
    short_description: null,
    description: null,
    status: "active",
    is_featured: false,
    brand: null,
    weight_grams: null,
    published_at: null,
    category: null,
    variants: [],
    meta: {
        title: null,
        description: null,
    },
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

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    vi.useFakeTimers();
    listAdminCategoryOptionsMock.mockResolvedValue([]);
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
        expect(getAdminOrderDetailMock).toHaveBeenCalledWith("order-3", expect.anything());

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

    it("keeps selected order detail stable when older detail request resolves later", async () => {
        listAdminOrdersMock.mockResolvedValue(
            buildListResponse([buildOrderSummary("order-1"), buildOrderSummary("order-2")], 1),
        );
        const detailRequests: Array<{
            orderId: string;
            deferred: ReturnType<typeof createDeferred<AdminOrderDetail | null>>;
        }> = [];
        getAdminOrderDetailMock.mockImplementation((orderId: string) => {
            const deferred = createDeferred<AdminOrderDetail | null>();
            detailRequests.push({
                orderId,
                deferred,
            });

            return deferred.promise;
        });

        const scope = effectScope();
        const api = scope.run(() => useAdminOrders());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        const firstLoad = api.loadOrders(1);
        await vi.waitFor(() => {
            expect(getAdminOrderDetailMock).toHaveBeenCalledWith("order-1", expect.anything());
        });

        const selectSecondOrder = api.selectOrder("order-2");
        await vi.waitFor(() => {
            expect(getAdminOrderDetailMock).toHaveBeenCalledWith("order-2", expect.anything());
        });

        detailRequests
            .find((request) => request.orderId === "order-2")
            ?.deferred.resolve(buildOrderDetail("order-2"));
        await selectSecondOrder;
        expect(api.selectedOrderDetail.value?.id).toBe("order-2");

        detailRequests
            .find((request) => request.orderId === "order-1")
            ?.deferred.resolve(buildOrderDetail("order-1"));
        await firstLoad;

        expect(api.selectedOrderId.value).toBe("order-2");
        expect(api.selectedOrderDetail.value?.id).toBe("order-2");

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

    it("reloads products from first page when search filter changes", async () => {
        listAdminProductsMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);

            return buildListResponse([buildProduct(page)], page);
        });

        const scope = effectScope();
        const api = scope.run(() => useAdminProducts());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadProducts(4);

        expect(listAdminProductsMock).toHaveBeenLastCalledWith({
            page: 4,
        });

        api.searchQuery.value = "  hoodie  ";
        await nextTick();

        await vi.advanceTimersByTimeAsync(300);

        expect(listAdminProductsMock).toHaveBeenLastCalledWith({
            page: 1,
            q: "hoodie",
        });
        expect(api.page.value).toBe(1);

        scope.stop();
    });

    it("syncs orders filters and page with route query when routeSync is enabled", async () => {
        listAdminOrdersMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);

            return buildListResponse([buildOrderSummary(`order-${page}`)], page);
        });
        getAdminOrderDetailMock.mockImplementation(async (orderId: string) =>
            buildOrderDetail(orderId),
        );

        const route = reactive<{ query: Record<string, unknown> }>({
            query: {},
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminOrders({
                routeSync: {
                    route,
                    router: { replace },
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadOrders(3);

        expect(replace).toHaveBeenCalledWith({
            query: {
                page: "3",
            },
        });
        expect(listAdminOrdersMock).toHaveBeenLastCalledWith({
            page: 3,
        });

        api.filters.search = "  jane@example.com  ";
        await nextTick();
        api.filters.orderStatus = "completed";
        await nextTick();
        api.filters.paymentStatus = "captured";
        await nextTick();
        api.filters.shipmentStatus = "delivered";
        await nextTick();

        await vi.advanceTimersByTimeAsync(300);

        expect(replace).toHaveBeenCalledWith({
            query: {
                q: "jane@example.com",
                status: "completed",
                payment_status: "captured",
                shipment_status: "delivered",
            },
        });
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

    it("syncs promotions filters and page with route query when routeSync is enabled", async () => {
        listPromotionsMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);

            return buildListResponse([buildPromotion(page)], page);
        });

        const route = reactive<{ query: Record<string, unknown> }>({
            query: {
                q: "  vip ",
                status: "active",
                page: "2",
            },
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotions({
                routeSync: {
                    route,
                    router: { replace },
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadPromotions();
        expect(listPromotionsMock).toHaveBeenLastCalledWith({
            page: 2,
            q: "vip",
            is_active: true,
        });

        api.searchQuery.value = "  winter  ";
        await nextTick();
        api.statusFilter.value = "inactive";
        await nextTick();

        await vi.advanceTimersByTimeAsync(300);

        expect(replace).toHaveBeenCalledWith({
            query: {
                q: "winter",
                status: "inactive",
            },
        });
        expect(listPromotionsMock).toHaveBeenLastCalledWith({
            page: 1,
            q: "winter",
            is_active: false,
        });

        scope.stop();
    });

    it("syncs categories filters and page with route query when routeSync is enabled", async () => {
        listAdminCategoriesMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);

            return buildListResponse([buildCategory(page)], page);
        });

        const route = reactive<{ query: Record<string, unknown> }>({
            query: {
                q: "  shoes ",
                status: "active",
                page: "2",
            },
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategories({
                routeSync: {
                    route,
                    router: { replace },
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadCategories();
        expect(listAdminCategoriesMock).toHaveBeenLastCalledWith({
            page: 2,
            per_page: 200,
            q: "shoes",
            is_active: true,
        });

        api.searchQuery.value = "  boots ";
        await nextTick();
        api.statusFilter.value = "inactive";
        await nextTick();

        await vi.advanceTimersByTimeAsync(300);

        expect(replace).toHaveBeenCalledWith({
            query: {
                q: "boots",
                status: "inactive",
            },
        });
        expect(listAdminCategoriesMock).toHaveBeenLastCalledWith({
            page: 1,
            per_page: 200,
            q: "boots",
            is_active: false,
        });

        scope.stop();
    });

    it("syncs products filters and page with route query when routeSync is enabled", async () => {
        listAdminProductsMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);

            return buildListResponse([buildProduct(page)], page);
        });

        const route = reactive<{ query: Record<string, unknown> }>({
            query: {
                q: "  jacket ",
                page: "3",
            },
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProducts({
                routeSync: {
                    route,
                    router: { replace },
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadProducts();
        expect(listAdminProductsMock).toHaveBeenLastCalledWith({
            page: 3,
            q: "jacket",
        });

        api.searchQuery.value = "  boots ";
        await nextTick();

        await vi.advanceTimersByTimeAsync(300);

        expect(replace).toHaveBeenCalledWith({
            query: {
                q: "boots",
            },
        });
        expect(listAdminProductsMock).toHaveBeenLastCalledWith({
            page: 1,
            q: "boots",
        });

        scope.stop();
    });

    it("does not duplicate products reload after route-synced search normalization", async () => {
        listAdminProductsMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);

            return buildListResponse([buildProduct(page)], page);
        });

        const route = reactive<{ query: Record<string, unknown> }>({
            query: {},
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProducts({
                routeSync: {
                    route,
                    router: { replace },
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        listAdminProductsMock.mockClear();
        replace.mockClear();

        api.searchQuery.value = "  boots ";
        await nextTick();
        await vi.advanceTimersByTimeAsync(300);

        await vi.waitFor(() => {
            expect(listAdminProductsMock).toHaveBeenCalledWith({
                page: 1,
                q: "boots",
            });
        });

        await vi.advanceTimersByTimeAsync(300);

        expect(listAdminProductsMock).toHaveBeenCalledTimes(1);
        expect(replace).toHaveBeenCalledTimes(1);
        expect(replace).toHaveBeenCalledWith({
            query: {
                q: "boots",
            },
        });

        scope.stop();
    });

    it("applies external products route updates without first-page regression", async () => {
        listAdminProductsMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);

            return buildListResponse([buildProduct(page)], page);
        });

        const route = reactive<{ query: Record<string, unknown> }>({
            query: {},
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProducts({
                routeSync: {
                    route,
                    router: { replace },
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        listAdminProductsMock.mockClear();
        replace.mockClear();

        route.query = {
            q: "winter",
            page: "3",
        };
        await nextTick();

        await vi.waitFor(() => {
            expect(listAdminProductsMock).toHaveBeenCalledWith({
                page: 3,
                q: "winter",
            });
        });

        await vi.advanceTimersByTimeAsync(300);

        expect(listAdminProductsMock).toHaveBeenCalledTimes(1);
        expect(replace).not.toHaveBeenCalled();
        expect(api.page.value).toBe(3);

        scope.stop();
    });
});
