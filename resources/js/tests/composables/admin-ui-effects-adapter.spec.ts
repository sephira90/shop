import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope } from "vue";
import { createPinia, setActivePinia } from "pinia";

import { useAdminCategories } from "@/composables/admin/useAdminCategories";
import { useAdminProducts } from "@/composables/admin/useAdminProducts";
import { useAdminPromotions } from "@/composables/admin/useAdminPromotions";
import { useAuthStore } from "@/stores/auth";
import type { AdminCategory } from "@/types/admin-categories";
import type { AdminProduct } from "@/types/admin-products";
import type { Promotion } from "@/types/admin-promotions";

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

vi.mock("@/api/admin/products", () => ({
    listAdminProducts: vi.fn(),
    createAdminProduct: vi.fn(),
    updateAdminProduct: vi.fn(),
    deleteAdminProduct: vi.fn(),
    refreshAdminCatalogCache: vi.fn(),
}));

import { deleteAdminCategory } from "@/api/admin/categories";
import { deleteAdminProduct } from "@/api/admin/products";
import { deletePromotion } from "@/api/admin/promotions";

const deletePromotionMock = deletePromotion as unknown as ReturnType<typeof vi.fn>;
const deleteAdminCategoryMock = deleteAdminCategory as unknown as ReturnType<typeof vi.fn>;
const deleteAdminProductMock = deleteAdminProduct as unknown as ReturnType<typeof vi.fn>;

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
    status: "draft",
    is_featured: false,
    brand: null,
    weight_grams: null,
    category: null,
    meta: {
        title: null,
        description: null,
    },
    variants: [],
    published_at: null,
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
});

describe("admin composables ui effects adapter", () => {
    it("uses injected scroll and confirm adapters in promotions flow", async () => {
        const confirm = vi.fn().mockResolvedValue(false);
        const scrollToTop = vi.fn();
        const promotion = buildPromotion(7);
        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotions({
                uiEffects: {
                    confirm,
                    scrollToTop,
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.startEditPromotion(promotion);
        expect(scrollToTop).toHaveBeenCalledTimes(1);

        await api.removePromotion(promotion);
        expect(confirm).toHaveBeenCalledWith('Delete campaign "Campaign 7"?');
        expect(deletePromotionMock).not.toHaveBeenCalled();

        scope.stop();
    });

    it("uses injected scroll and confirm adapters in categories flow", async () => {
        setAdminRole();
        const confirm = vi.fn().mockResolvedValue(false);
        const scrollToTop = vi.fn();
        const category = buildCategory(9);
        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategories({
                uiEffects: {
                    confirm,
                    scrollToTop,
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.startEdit(category);
        expect(scrollToTop).toHaveBeenCalledTimes(1);

        await api.removeCategory(category);
        expect(confirm).toHaveBeenCalledWith('Delete category "Category 9"?');
        expect(deleteAdminCategoryMock).not.toHaveBeenCalled();

        scope.stop();
    });

    it("uses injected scroll and confirm adapters in products flow", async () => {
        setAdminRole();
        const confirm = vi.fn().mockResolvedValue(false);
        const scrollToTop = vi.fn();
        const product = buildProduct(11);
        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProducts({
                uiEffects: {
                    confirm,
                    scrollToTop,
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.startEdit(product);
        expect(scrollToTop).toHaveBeenCalledTimes(1);

        await api.removeProduct(product);
        expect(confirm).toHaveBeenCalledWith('Delete product "Product 11"?');
        expect(deleteAdminProductMock).not.toHaveBeenCalled();

        scope.stop();
    });
});
