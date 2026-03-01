/* @vitest-environment jsdom */

import { RouterLinkStub, mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import AccountTabsNav from "@/components/account/AccountTabsNav.vue";
import AccountOrderCard from "@/components/account/orders/AccountOrderCard.vue";
import AccountOrdersFiltersBar from "@/components/account/orders/AccountOrdersFiltersBar.vue";
import AccountOrdersPaginationCard from "@/components/account/orders/AccountOrdersPaginationCard.vue";
import type { AccountOrderDetail, AccountOrderSummary } from "@/types/account-orders";
import type { PaginationMeta } from "@/types/pagination";

const meta: PaginationMeta = {
    current_page: 2,
    last_page: 5,
    total: 120,
    per_page: 30,
};

const buildSummaryOrder = (): AccountOrderSummary => ({
    id: "ord-501",
    order_number: "SO-501",
    email: "buyer@example.com",
    status: "pending",
    payment_status: "pending",
    shipment_status: "packed",
    currency: "USD",
    total: 150,
    placed_at: "2026-02-20T10:00:00Z",
    created_at: "2026-02-20T09:00:00Z",
});

const buildDetailOrder = (): AccountOrderDetail => ({
    ...buildSummaryOrder(),
    subtotal: 150,
    discount_total: 0,
    shipping_total: 0,
    items: [
        {
            product_variant_id: 10,
            sku: "SKU-1",
            name: "Item 1",
            quantity: 2,
            unit_price: 75,
            total_price: 150,
        },
    ],
    payments: [],
    shipments: [],
    billing_address: {
        line1: "Main st 1",
        city: "New York",
        country: "US",
        postcode: "10001",
    },
    shipping_address: {
        line1: "Main st 2",
        city: "New York",
        country: "US",
        postcode: "10002",
    },
});

describe("account tabs nav contract", () => {
    it("renders profile and orders links", () => {
        const wrapper = mount(AccountTabsNav, {
            global: {
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        const links = wrapper.findAllComponents(RouterLinkStub);
        expect(links).toHaveLength(2);
        expect(links[0].props("to")).toBe("/account/profile");
        expect(links[1].props("to")).toBe("/account/orders");
    });
});

describe("account orders filters bar contract", () => {
    it("updates models and emits apply on enter/change", async () => {
        const wrapper = mount(AccountOrdersFiltersBar, {
            props: {
                isLoading: false,
                searchQuery: "",
                statusFilter: "all",
            },
        });

        const input = wrapper.get("input");
        await input.setValue("SO-501");
        await input.trigger("keyup.enter");

        const select = wrapper.get("select");
        await select.setValue("completed");

        expect(wrapper.emitted("update:searchQuery")?.[0]).toEqual(["SO-501"]);
        expect(wrapper.emitted("update:statusFilter")?.[0]).toEqual(["completed"]);
        expect(wrapper.emitted("apply")).toHaveLength(2);
    });
});

describe("account order card contract", () => {
    it("emits toggle and renders details when expanded", async () => {
        const order = buildSummaryOrder();
        const detail = buildDetailOrder();
        const wrapper = mount(AccountOrderCard, {
            props: {
                order,
                detail,
                expanded: true,
                isDetailLoading: false,
                detailError: "",
                totalItems: (target) =>
                    target?.items.reduce((sum, item) => sum + item.quantity, 0) ?? 0,
                formatPrice: (value: number, currency?: string) => `${currency ?? "USD"} ${value}`,
                formatDate: (value: string | null) => value ?? "Unknown date",
                formatAddress: (address) => address?.line1 ?? "Unknown address",
                orderStatusTone: () => "warn",
                paymentStatusTone: () => "warn",
                shipmentStatusTone: () => "warn",
            },
        });

        await wrapper.get("button").trigger("click");

        expect(wrapper.emitted("toggleDetails")?.[0]).toEqual([order.id]);
        expect(wrapper.text()).toContain("Billing address");
        expect(wrapper.text()).toContain("Shipping address");
        expect(wrapper.text()).toContain("Item 1");
    });
});

describe("account orders pagination contract", () => {
    it("emits previous and next page events", async () => {
        const wrapper = mount(AccountOrdersPaginationCard, {
            props: {
                page: 2,
                isLoading: false,
                meta,
            },
        });

        const buttons = wrapper.findAll("button");
        await buttons[0].trigger("click");
        await buttons[1].trigger("click");

        expect(wrapper.emitted("loadPrev")).toHaveLength(1);
        expect(wrapper.emitted("loadNext")).toHaveLength(1);
    });
});
