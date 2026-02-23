/* @vitest-environment jsdom */

import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import CartEmptyState from "@/components/cart/CartEmptyState.vue";
import CartItemsTable from "@/components/cart/CartItemsTable.vue";
import CartQuantityControl from "@/components/cart/CartQuantityControl.vue";
import CartSummaryHeader from "@/components/cart/CartSummaryHeader.vue";
import type { CartItem } from "@/stores/cart";

const buildItem = (): CartItem => ({
    product_variant_id: 101,
    sku: "SKU-101",
    name: "Essential Hoodie",
    quantity: 2,
    unit_price: 49.9,
    line_total: 99.8,
});

describe("cart summary header contract", () => {
    it("renders formatted total", () => {
        const wrapper = mount(CartSummaryHeader, {
            props: {
                total: 123.4,
            },
        });

        expect(wrapper.text()).toContain("Cart");
        expect(wrapper.text()).toContain("Total: 123.40");
    });
});

describe("cart quantity control contract", () => {
    it("emits increment, decrement and normalized quantity update", async () => {
        const wrapper = mount(CartQuantityControl, {
            props: {
                quantity: 2,
            },
        });

        await wrapper.get('[data-testid="cart-qty-decrease"]').trigger("click");
        await wrapper.get('[data-testid="cart-qty-increase"]').trigger("click");
        await wrapper.get('[data-testid="cart-qty-input"]').setValue("2001");
        await wrapper.get('[data-testid="cart-qty-input"]').trigger("change");

        expect(wrapper.emitted("decrease")).toHaveLength(1);
        expect(wrapper.emitted("increase")).toHaveLength(1);
        expect(wrapper.emitted("updateQuantity")?.at(-1)).toEqual([1000]);
    });
});

describe("cart items table contract", () => {
    it("renders row and emits table action events", async () => {
        const item = buildItem();
        const wrapper = mount(CartItemsTable, {
            props: {
                items: [item],
            },
            global: {
                stubs: {
                    RouterLink: {
                        template: "<a><slot /></a>",
                    },
                },
            },
        });

        await wrapper.get('[data-testid="cart-qty-decrease"]').trigger("click");
        await wrapper.get('[data-testid="cart-qty-increase"]').trigger("click");
        await wrapper.get('[data-testid="cart-qty-input"]').setValue("7");
        await wrapper.get('[data-testid="cart-qty-input"]').trigger("change");
        await wrapper.get('[data-testid="cart-remove-item"]').trigger("click");

        expect(wrapper.text()).toContain("Essential Hoodie");
        expect(wrapper.emitted("decreaseQuantity")?.[0]).toEqual([item]);
        expect(wrapper.emitted("increaseQuantity")?.[0]).toEqual([item]);
        expect(wrapper.emitted("updateQuantity")?.[0]).toEqual([{ item, quantity: 7 }]);
        expect(wrapper.emitted("removeItem")?.[0]).toEqual([item.product_variant_id]);
    });
});

describe("cart empty state contract", () => {
    it("renders empty cart message", () => {
        const wrapper = mount(CartEmptyState);
        expect(wrapper.text()).toContain("Cart is empty");
    });
});
