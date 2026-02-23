/* @vitest-environment jsdom */

import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import OrderItemsTable from "@/components/orders/OrderItemsTable.vue";

describe("order items table component contract", () => {
    it("renders table rows and delegates price formatting", () => {
        const wrapper = mount(OrderItemsTable, {
            props: {
                items: [
                    {
                        name: "Trail Boots",
                        sku: "TB-001",
                        quantity: 2,
                        unit_price: 50,
                        total_price: 100,
                    },
                ],
                currency: "USD",
                itemKeyPrefix: "ord-1001",
                formatPrice: (value: number, currency?: string) => `${currency ?? "USD"} ${value}`,
            },
        });

        expect(wrapper.find("table.table").exists()).toBe(true);
        expect(wrapper.text()).toContain("Trail Boots");
        expect(wrapper.text()).toContain("TB-001");
        expect(wrapper.text()).toContain("USD 50");
        expect(wrapper.text()).toContain("USD 100");
    });

    it("supports top spacing mode and hides empty sku label", () => {
        const wrapper = mount(OrderItemsTable, {
            props: {
                items: [
                    {
                        name: "Accessory",
                        sku: "",
                        quantity: 1,
                        unit_price: 10,
                        total_price: 10,
                    },
                ],
                itemKeyPrefix: "ord-1002",
                withTopSpacing: true,
                formatPrice: (value: number, currency?: string) => `${currency ?? "USD"} ${value}`,
            },
        });

        expect(wrapper.find(".table-wrap").classes()).toContain("actions--top");
        expect(wrapper.text()).toContain("Accessory");
        expect(wrapper.find(".muted").exists()).toBe(false);
    });
});
