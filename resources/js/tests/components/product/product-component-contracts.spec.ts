/* @vitest-environment jsdom */

import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import ProductInfoCard from "@/components/product/ProductInfoCard.vue";
import ProductPurchaseCard from "@/components/product/ProductPurchaseCard.vue";
import type { CatalogProduct } from "@/types/catalog";

const buildProduct = (): CatalogProduct => ({
    id: 5,
    name: "Urban Sneakers",
    slug: "urban-sneakers",
    short_description: "Comfort shoes for city walks.",
    description: "Full description",
    variants: [
        {
            id: 501,
            sku: "US-501",
            name: "40",
            price: 89.9,
            currency: "USD",
            is_active: true,
        },
        {
            id: 502,
            sku: "US-502",
            name: "41",
            price: 92.5,
            currency: "USD",
            is_active: true,
        },
    ],
});

describe("product info card contract", () => {
    it("renders product main information", () => {
        const product = buildProduct();
        const wrapper = mount(ProductInfoCard, {
            props: {
                product,
            },
        });

        expect(wrapper.text()).toContain("Urban Sneakers");
        expect(wrapper.text()).toContain("Full description");
    });
});

describe("product purchase card contract", () => {
    it("updates selected variant id and emits add-to-cart", async () => {
        const product = buildProduct();
        const wrapper = mount(ProductPurchaseCard, {
            props: {
                product,
                selectedVariant: product.variants[0],
                selectedVariantId: product.variants[0].id,
                formatPrice: (value: number | undefined) => Number(value ?? 0).toFixed(2),
            },
        });

        await wrapper.get("select").setValue(String(product.variants[1].id));
        await wrapper.get("button").trigger("click");

        expect(wrapper.emitted("update:selectedVariantId")?.[0]).toEqual([product.variants[1].id]);
        expect(wrapper.emitted("addToCart")).toHaveLength(1);
    });
});
