import { describe, expect, it } from "vitest";

import type { AdminProduct, ProductVariantForm } from "@/types/admin-products";
import {
    buildProductMutationPayload,
    buildProductMutationPayloadFromProduct,
    createProductFormState,
} from "@/validators/admin/products";

const buildVariantForm = (overrides: Partial<ProductVariantForm> = {}): ProductVariantForm => ({
    local_id: 1,
    id: 10,
    sku: "SKU-RED-M",
    name: "Red / M",
    price: "129.95",
    compare_at_price: "",
    currency: "usd",
    is_active: true,
    attributes_json: '{"color":"red","size":"m"}',
    inventory_quantity: "5",
    inventory_reserved_quantity: "10",
    inventory_low_stock_threshold: "2",
    ...overrides,
});

describe("product validator", () => {
    it("creates default form state", () => {
        const variants = [buildVariantForm()];
        const state = createProductFormState(variants);

        expect(state.status).toBe("draft");
        expect(state.variants).toEqual(variants);
        expect(state.name).toBe("");
    });

    it("builds product payload and normalizes variants", () => {
        const payload = buildProductMutationPayload({
            sku: "  SKU-001  ",
            name: "  Winter Jacket  ",
            slug: "  winter-jacket  ",
            short_description: "  Warm  ",
            description: "  Full description  ",
            status: "active",
            is_featured: true,
            category_id: "3",
            brand: "  ACME  ",
            weight_grams: "450",
            meta_title: "  Meta title  ",
            meta_description: "  Meta desc  ",
            published_at: "2026-02-21T10:00:00.000Z",
            variants: [buildVariantForm()],
        });

        expect(payload.sku).toBe("SKU-001");
        expect(payload.name).toBe("Winter Jacket");
        expect(payload.slug).toBe("winter-jacket");
        expect(payload.category_id).toBe(3);
        expect(payload.weight_grams).toBe(450);
        expect(payload.published_at).toBe("2026-02-21T10:00:00.000Z");
        expect(payload.variants).toEqual([
            {
                id: 10,
                sku: "SKU-RED-M",
                name: "Red / M",
                attributes: {
                    color: "red",
                    size: "m",
                },
                price: 129.95,
                compare_at_price: null,
                currency: "USD",
                is_active: true,
                inventory: {
                    quantity: 5,
                    reserved_quantity: 5,
                    low_stock_threshold: 2,
                },
            },
        ]);
    });

    it("throws on duplicate variant sku", () => {
        expect(() =>
            buildProductMutationPayload({
                ...createProductFormState([buildVariantForm(), buildVariantForm({ local_id: 2 })]),
                sku: "S",
                name: "N",
            }),
        ).toThrow("duplicate SKU");
    });

    it("throws on invalid variant attributes json", () => {
        expect(() =>
            buildProductMutationPayload({
                ...createProductFormState([buildVariantForm({ attributes_json: '{"invalid"' })]),
                sku: "S",
                name: "N",
            }),
        ).toThrow("attributes must be valid JSON");
    });

    it("builds payload from existing product", () => {
        const product: AdminProduct = {
            id: 1,
            sku: "SKU-PRODUCT",
            name: "Product",
            slug: "product",
            short_description: null,
            description: "Description",
            status: "draft",
            is_featured: false,
            brand: null,
            weight_grams: null,
            category: {
                id: 20,
                name: "Category",
                slug: "category",
            },
            meta: {
                title: "Meta",
                description: null,
            },
            variants: [],
            published_at: null,
        };

        expect(buildProductMutationPayloadFromProduct(product)).toEqual({
            sku: "SKU-PRODUCT",
            name: "Product",
            slug: "product",
            short_description: null,
            description: "Description",
            status: "draft",
            is_featured: false,
            category_id: 20,
            brand: null,
            weight_grams: null,
            meta_title: "Meta",
            meta_description: null,
            published_at: null,
        });
    });
});
