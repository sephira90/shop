import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope } from "vue";
import { createPinia, setActivePinia } from "pinia";

import type { ListResponse } from "@/api/response";
import { useAdminCategories } from "@/composables/admin/useAdminCategories";
import { useAdminProducts } from "@/composables/admin/useAdminProducts";
import { useAdminPromotions } from "@/composables/admin/useAdminPromotions";
import { useAuthStore } from "@/stores/auth";
import type { AdminCategory } from "@/types/admin-categories";
import type { AdminProduct } from "@/types/admin-products";
import type { Promotion } from "@/types/admin-promotions";

vi.mock("@/api/admin/products", () => ({
    listAdminProducts: vi.fn(),
    createAdminProduct: vi.fn(),
    updateAdminProduct: vi.fn(),
    deleteAdminProduct: vi.fn(),
    refreshAdminCatalogCache: vi.fn(),
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

import {
    deleteAdminCategory,
    listAdminCategories,
    updateAdminCategory,
} from "@/api/admin/categories";
import { deleteAdminProduct, listAdminProducts, updateAdminProduct } from "@/api/admin/products";
import { deletePromotion, listPromotions, updatePromotion } from "@/api/admin/promotions";

const listAdminProductsMock = listAdminProducts as unknown as ReturnType<typeof vi.fn>;
const updateAdminProductMock = updateAdminProduct as unknown as ReturnType<typeof vi.fn>;
const deleteAdminProductMock = deleteAdminProduct as unknown as ReturnType<typeof vi.fn>;

const listPromotionsMock = listPromotions as unknown as ReturnType<typeof vi.fn>;
const updatePromotionMock = updatePromotion as unknown as ReturnType<typeof vi.fn>;
const deletePromotionMock = deletePromotion as unknown as ReturnType<typeof vi.fn>;

const listAdminCategoriesMock = listAdminCategories as unknown as ReturnType<typeof vi.fn>;
const updateAdminCategoryMock = updateAdminCategory as unknown as ReturnType<typeof vi.fn>;
const deleteAdminCategoryMock = deleteAdminCategory as unknown as ReturnType<typeof vi.fn>;

const buildListResponse = <TItem>(items: TItem[], page: number): ListResponse<TItem> => ({
    data: items,
    meta: {
        current_page: page,
        last_page: 5,
        per_page: 30,
        total: 150,
    },
});

const buildProduct = (id: number): AdminProduct => ({
    id,
    sku: `SKU-${id}`,
    name: `Product ${id}`,
    slug: `product-${id}`,
    short_description: null,
    description: null,
    status: "draft",
    is_featured: false,
    brand: null,
    weight_grams: null,
    category: null,
    meta: {
        title: null,
        description: null,
    },
    variants: [
        {
            id: id * 10,
            sku: `SKU-${id}-1`,
            name: `Variant ${id}`,
            attributes: { size: "M" },
            price: 10,
            compare_at_price: null,
            currency: "USD",
            is_active: true,
            inventory: {
                quantity: 5,
                reserved_quantity: 0,
                low_stock_threshold: 3,
            },
        },
    ],
    published_at: null,
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

const setAdminRole = (): void => {
    const authStore = useAuthStore();
    authStore.user = {
        id: 1,
        name: "Admin User",
        email: "admin@example.com",
        roles: ["admin"],
    };
};

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
});

describe("admin mutation list stability flows", () => {
    it("keeps products page and filters after update", async () => {
        listAdminProductsMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);
            return buildListResponse([buildProduct(page)], page);
        });
        updateAdminProductMock.mockResolvedValue(buildProduct(3));

        const scope = effectScope();
        const api = scope.run(() => useAdminProducts());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.searchQuery.value = "  laptop  ";
        await api.loadProducts(3);

        const target = api.products.value[0];
        api.startEdit(target);
        await api.submitProduct();

        expect(updateAdminProductMock).toHaveBeenCalledWith(
            target.id,
            expect.objectContaining({
                sku: target.sku,
                name: target.name,
            }),
        );
        expect(listAdminProductsMock).toHaveBeenLastCalledWith({
            page: 3,
            q: "laptop",
        });
        expect(api.page.value).toBe(3);

        scope.stop();
    });

    it("falls back to previous products page after deleting last item and keeps filters", async () => {
        setAdminRole();
        listAdminProductsMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);
            return buildListResponse([buildProduct(page)], page);
        });
        deleteAdminProductMock.mockResolvedValue(undefined);
        const confirm = vi.fn().mockResolvedValue(true);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProducts({
                uiEffects: {
                    confirm,
                    scrollToTop: vi.fn(),
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.searchQuery.value = "  pro  ";
        await api.loadProducts(2);

        const target = api.products.value[0];
        await api.removeProduct(target);

        expect(confirm).toHaveBeenCalledWith(`Delete product "${target.name}"?`);
        expect(deleteAdminProductMock).toHaveBeenCalledWith(target.id);
        expect(listAdminProductsMock).toHaveBeenLastCalledWith({
            page: 1,
            q: "pro",
        });
        expect(api.page.value).toBe(1);

        scope.stop();
    });

    it("keeps promotions page and filters after update", async () => {
        listPromotionsMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);
            return buildListResponse([buildPromotion(page)], page);
        });
        updatePromotionMock.mockResolvedValue(buildPromotion(4));

        const scope = effectScope();
        const api = scope.run(() => useAdminPromotions());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.searchQuery.value = "  vip  ";
        api.statusFilter.value = "active";
        await api.loadPromotions(4);

        const target = api.promotions.value[0];
        api.startEditPromotion(target);
        await api.submitPromotion();

        expect(updatePromotionMock).toHaveBeenCalledWith(
            target.id,
            expect.objectContaining({
                name: target.name,
                code: target.code,
            }),
        );
        expect(listPromotionsMock).toHaveBeenLastCalledWith({
            page: 4,
            q: "vip",
            is_active: true,
        });
        expect(api.selectedPromotionId.value).toBe(target.id);
        expect(api.page.value).toBe(4);

        scope.stop();
    });

    it("falls back to previous promotions page after deleting last item and keeps filters", async () => {
        listPromotionsMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);
            return buildListResponse([buildPromotion(page)], page);
        });
        deletePromotionMock.mockResolvedValue(undefined);
        const confirm = vi.fn().mockResolvedValue(true);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotions({
                uiEffects: {
                    confirm,
                    scrollToTop: vi.fn(),
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.searchQuery.value = "  winter  ";
        api.statusFilter.value = "inactive";
        await api.loadPromotions(2);

        const target = api.promotions.value[0];
        await api.removePromotion(target);

        expect(confirm).toHaveBeenCalledWith(`Delete campaign "${target.name}"?`);
        expect(deletePromotionMock).toHaveBeenCalledWith(target.id);
        expect(listPromotionsMock).toHaveBeenLastCalledWith({
            page: 1,
            q: "winter",
            is_active: false,
        });
        expect(api.page.value).toBe(1);

        scope.stop();
    });

    it("keeps categories page and filters after update", async () => {
        listAdminCategoriesMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);
            return buildListResponse([buildCategory(page)], page);
        });
        updateAdminCategoryMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() => useAdminCategories());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.searchQuery.value = "  shoes  ";
        api.statusFilter.value = "active";
        await api.loadCategories(3);

        const target = api.categories.value[0];
        api.startEdit(target);
        await api.submitCategory();

        expect(updateAdminCategoryMock).toHaveBeenCalledWith(
            target.id,
            expect.objectContaining({
                name: target.name,
            }),
        );
        expect(listAdminCategoriesMock).toHaveBeenLastCalledWith({
            page: 3,
            per_page: 200,
            q: "shoes",
            is_active: true,
        });
        expect(api.page.value).toBe(3);

        scope.stop();
    });

    it("falls back to previous categories page after deleting last item and keeps filters", async () => {
        setAdminRole();
        listAdminCategoriesMock.mockImplementation(async (params: Record<string, unknown>) => {
            const page = Number(params.page ?? 1);
            return buildListResponse([buildCategory(page)], page);
        });
        deleteAdminCategoryMock.mockResolvedValue(undefined);
        const confirm = vi.fn().mockResolvedValue(true);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategories({
                uiEffects: {
                    confirm,
                    scrollToTop: vi.fn(),
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.searchQuery.value = "  boots  ";
        api.statusFilter.value = "inactive";
        await api.loadCategories(2);

        const target = api.categories.value[0];
        await api.removeCategory(target);

        expect(confirm).toHaveBeenCalledWith(`Delete category "${target.name}"?`);
        expect(deleteAdminCategoryMock).toHaveBeenCalledWith(target.id);
        expect(listAdminCategoriesMock).toHaveBeenLastCalledWith({
            page: 1,
            per_page: 200,
            q: "boots",
            is_active: false,
        });
        expect(api.page.value).toBe(1);

        scope.stop();
    });
});
