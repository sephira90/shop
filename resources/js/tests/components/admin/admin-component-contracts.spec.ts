/* @vitest-environment jsdom */

import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import AdminCategoriesListCard from "@/components/admin/categories/AdminCategoriesListCard.vue";
import AdminOrdersTableCard from "@/components/admin/orders/AdminOrdersTableCard.vue";
import AdminProductsListCard from "@/components/admin/products/AdminProductsListCard.vue";
import type { AdminCategory } from "@/types/admin-categories";
import type { AdminOrderSummary } from "@/types/admin-orders";
import type { AdminProduct } from "@/types/admin-products";
import type { PaginationMeta } from "@/types/pagination";

const paginationMeta: PaginationMeta = {
    current_page: 2,
    last_page: 3,
    total: 60,
    per_page: 30,
};

const buildProduct = (): AdminProduct => ({
    id: 21,
    sku: "SKU-21",
    name: "Product 21",
    slug: "product-21",
    short_description: null,
    description: null,
    status: "draft",
    is_featured: false,
    brand: null,
    weight_grams: null,
    category: {
        id: 8,
        name: "Shoes",
        slug: "shoes",
    },
    meta: {
        title: null,
        description: null,
    },
    variants: [],
    published_at: "2026-02-20T10:00:00Z",
});

const buildOrder = (): AdminOrderSummary => ({
    id: "ord-1001",
    order_number: "SO-1001",
    email: "customer@example.com",
    status: "pending",
    payment_status: "pending",
    shipment_status: "pending",
    currency: "USD",
    total: 120.5,
    placed_at: "2026-02-20T12:30:00Z",
    created_at: "2026-02-20T12:00:00Z",
});

const buildCategory = (): AdminCategory => ({
    id: 3,
    parent_id: null,
    name: "Boots",
    slug: "boots",
    description: null,
    meta_title: null,
    meta_description: null,
    is_active: true,
    sort_order: 12,
    parent: null,
    children_count: 1,
    products_count: 10,
});

describe("admin products list component contract", () => {
    it("emits product actions and pagination events", async () => {
        const product = buildProduct();
        const wrapper = mount(AdminProductsListCard, {
            props: {
                products: [product],
                isLoading: false,
                page: 2,
                meta: paginationMeta,
                isDeletingId: null,
                isVisibilityUpdatingId: null,
                canDeleteProducts: true,
                searchQuery: "",
                statusBadgeTone: () => "inactive",
                isVisibleInCatalog: () => true,
            },
        });

        await wrapper
            .get("input[placeholder='Search products by name, SKU or slug']")
            .setValue("boots");
        const buttons = wrapper.findAll("button");
        expect(buttons).toHaveLength(6);

        await buttons[0].trigger("click");
        await buttons[1].trigger("click");
        await buttons[2].trigger("click");
        await buttons[3].trigger("click");
        await buttons[4].trigger("click");
        await buttons[5].trigger("click");

        expect(wrapper.emitted("update:searchQuery")?.[0]).toEqual(["boots"]);
        expect(wrapper.emitted("refresh")).toHaveLength(1);
        expect(wrapper.emitted("edit")?.[0]).toEqual([product]);
        expect(wrapper.emitted("toggleVisibility")?.[0]).toEqual([product]);
        expect(wrapper.emitted("remove")?.[0]).toEqual([product]);
        expect(wrapper.emitted("loadPrev")).toHaveLength(1);
        expect(wrapper.emitted("loadNext")).toHaveLength(1);
    });

    it("renders empty-state text when no products", () => {
        const wrapper = mount(AdminProductsListCard, {
            props: {
                products: [],
                isLoading: false,
                page: 1,
                meta: {
                    ...paginationMeta,
                    current_page: 1,
                },
                isDeletingId: null,
                isVisibilityUpdatingId: null,
                canDeleteProducts: true,
                searchQuery: "",
                statusBadgeTone: () => "inactive",
                isVisibleInCatalog: () => true,
            },
        });

        expect(wrapper.text()).toContain("No products on this page.");
    });
});

describe("admin orders table component contract", () => {
    it("emits select event with order id", async () => {
        const order = buildOrder();
        const wrapper = mount(AdminOrdersTableCard, {
            props: {
                orders: [order],
                isLoading: false,
                selectedOrderId: order.id,
                orderStatusTone: () => "warn",
                paymentStatusTone: () => "warn",
                shipmentStatusTone: () => "warn",
                formatPrice: (value: number, currency?: string) => `${currency ?? "USD"} ${value}`,
            },
        });

        await wrapper.get("button").trigger("click");

        expect(wrapper.find("tr.table-row-active").exists()).toBe(true);
        expect(wrapper.emitted("select")?.[0]).toEqual([order.id]);
    });

    it("renders empty-state text when list is empty", () => {
        const wrapper = mount(AdminOrdersTableCard, {
            props: {
                orders: [],
                isLoading: false,
                selectedOrderId: null,
                orderStatusTone: () => "warn",
                paymentStatusTone: () => "warn",
                shipmentStatusTone: () => "warn",
                formatPrice: (value: number, currency?: string) => `${currency ?? "USD"} ${value}`,
            },
        });

        expect(wrapper.text()).toContain("No orders match current filters.");
    });
});

describe("admin categories list component contract", () => {
    it("emits filters, row actions, refresh and pagination", async () => {
        const category = buildCategory();
        const wrapper = mount(AdminCategoriesListCard, {
            props: {
                categories: [category],
                isLoading: false,
                page: 2,
                meta: paginationMeta,
                isDeletingId: null,
                canDeleteCategories: true,
                searchQuery: "",
                statusFilter: "all",
            },
        });

        await wrapper.get("input[placeholder='Filter by name, slug or parent']").setValue("boots");
        await wrapper.get("select").setValue("inactive");

        const buttons = wrapper.findAll("button");
        await buttons[0].trigger("click");
        await buttons[1].trigger("click");
        await buttons[2].trigger("click");
        await buttons[3].trigger("click");
        await buttons[4].trigger("click");

        expect(wrapper.emitted("update:searchQuery")?.[0]).toEqual(["boots"]);
        expect(wrapper.emitted("update:statusFilter")?.[0]).toEqual(["inactive"]);
        expect(wrapper.emitted("refresh")).toHaveLength(1);
        expect(wrapper.emitted("edit")?.[0]).toEqual([category]);
        expect(wrapper.emitted("remove")?.[0]).toEqual([category]);
        expect(wrapper.emitted("loadPrev")).toHaveLength(1);
        expect(wrapper.emitted("loadNext")).toHaveLength(1);
    });
});
