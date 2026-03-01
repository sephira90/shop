/* @vitest-environment jsdom */

import { RouterLinkStub, mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import CatalogEmptyState from "@/components/catalog/CatalogEmptyState.vue";
import CatalogFiltersCard from "@/components/catalog/CatalogFiltersCard.vue";
import CatalogProductCard from "@/components/catalog/CatalogProductCard.vue";
import CatalogProductGrid from "@/components/catalog/CatalogProductGrid.vue";
import type { CatalogProduct } from "@/types/catalog";
import { formatPrice } from "@/utils/format";

const buildProduct = (): CatalogProduct => ({
    id: 7,
    name: "Trail Boots",
    slug: "trail-boots",
    short_description: "Lightweight hiking shoes.",
    description: "Full product description",
    variants: [
        {
            id: 701,
            sku: "TB-701",
            name: "42",
            price: 129.9,
            currency: "USD",
            is_active: true,
        },
    ],
});

describe("catalog filters card contract", () => {
    it("updates query/sort models and emits apply", async () => {
        const wrapper = mount(CatalogFiltersCard, {
            props: {
                query: "",
                sort: "newest",
                isLoading: false,
                loadError: "",
            },
        });

        await wrapper.get("input").setValue("boots");
        await wrapper.get("select").setValue("price_asc");
        await wrapper.get("button").trigger("click");

        expect(wrapper.emitted("update:query")?.[0]).toEqual(["boots"]);
        expect(wrapper.emitted("update:sort")?.[0]).toEqual(["price_asc"]);
        expect(wrapper.emitted("apply")).toHaveLength(1);
    });
});

describe("catalog product card contract", () => {
    it("renders link and emits add to cart", async () => {
        const product = buildProduct();
        const wrapper = mount(CatalogProductCard, {
            props: {
                product,
                formatPrice,
            },
            global: {
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        await wrapper.get("button").trigger("click");

        const link = wrapper.getComponent(RouterLinkStub);
        expect(link.props("to")).toBe("/product/trail-boots");
        expect(wrapper.text()).toContain("From $129.90");
        expect(wrapper.text()).not.toContain("USD");
        expect(wrapper.emitted("addToCart")?.[0]).toEqual([701]);
    });
});

describe("catalog product grid contract", () => {
    it("bubbles add-to-cart event from item cards", async () => {
        const wrapper = mount(CatalogProductGrid, {
            props: {
                products: [buildProduct()],
                formatPrice,
            },
            global: {
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        await wrapper.get("button").trigger("click");

        expect(wrapper.emitted("addToCart")?.[0]).toEqual([701]);
    });
});

describe("catalog empty state contract", () => {
    it("renders provided message", () => {
        const wrapper = mount(CatalogEmptyState, {
            props: {
                message: "No products found for current filters.",
            },
        });

        expect(wrapper.text()).toContain("No products found for current filters.");
    });
});
