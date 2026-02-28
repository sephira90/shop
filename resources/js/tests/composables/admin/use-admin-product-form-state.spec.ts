import { describe, expect, it, vi } from "vitest";
import { effectScope } from "vue";

import { useAdminProductFormState } from "@/composables/admin/products/useAdminProductFormState";
import { normalizeDatetimeForInput } from "@/utils/datetime";
import type { AdminProduct } from "@/types/admin-products";

const buildProduct = (id: number): AdminProduct => ({
    id,
    sku: `SKU-${id}`,
    name: `Product ${id}`,
    slug: `product-${id}`,
    short_description: "Short",
    description: "Long",
    status: "active",
    is_featured: true,
    brand: "Acme",
    weight_grams: 1200,
    category: {
        id: 7,
        name: "Shoes",
        slug: "shoes",
    },
    meta: {
        title: "Meta title",
        description: "Meta description",
    },
    variants: [
        {
            id: 11,
            sku: "SKU-11",
            name: "Variant 11",
            attributes: { size: "M" },
            price: 100,
            compare_at_price: 120,
            currency: "USD",
            is_active: true,
            inventory: {
                quantity: 10,
                reserved_quantity: 2,
                low_stock_threshold: 3,
            },
        },
        {
            id: 12,
            sku: "SKU-12",
            name: "Variant 12",
            attributes: null,
            price: 90,
            compare_at_price: null,
            currency: "USD",
            is_active: false,
            inventory: null,
        },
    ],
    published_at: "2026-02-28T12:00:00Z",
});

describe("useAdminProductFormState", () => {
    it("hydrates edit form from product and keeps variant helpers deterministic", () => {
        const clearNotice = vi.fn();
        const scrollToTop = vi.fn();

        const scope = effectScope();
        const state = scope.run(() =>
            useAdminProductFormState({
                clearNotice,
                scrollToTop,
            }),
        );

        expect(state).not.toBeNull();
        if (!state) {
            scope.stop();
            return;
        }

        expect(state.form.variants).toHaveLength(1);

        state.addVariant();
        expect(state.form.variants).toHaveLength(2);

        state.removeVariant(0);
        expect(state.form.variants).toHaveLength(1);

        state.removeVariant(0);
        expect(state.form.variants).toHaveLength(1);

        const product = buildProduct(5);
        state.startEdit(product);

        expect(state.editingId.value).toBe(5);
        expect(state.form.sku).toBe(product.sku);
        expect(state.form.name).toBe(product.name);
        expect(state.form.category_id).toBe("7");
        expect(state.form.meta_title).toBe("Meta title");
        expect(state.form.published_at).toBe(normalizeDatetimeForInput(product.published_at));
        expect(state.form.variants).toHaveLength(2);
        expect(state.form.variants[0]?.attributes_json).toContain('"size": "M"');
        expect(state.form.variants[1]?.attributes_json).toBe("{}");
        expect(clearNotice).toHaveBeenCalledTimes(1);
        expect(scrollToTop).toHaveBeenCalledTimes(1);

        scope.stop();
    });

    it("resets form and editing state to defaults", () => {
        const clearNotice = vi.fn();
        const scrollToTop = vi.fn();

        const scope = effectScope();
        const state = scope.run(() =>
            useAdminProductFormState({
                clearNotice,
                scrollToTop,
            }),
        );

        expect(state).not.toBeNull();
        if (!state) {
            scope.stop();
            return;
        }

        state.startEdit(buildProduct(8));
        state.resetForm();

        expect(state.editingId.value).toBeNull();
        expect(state.form.sku).toBe("");
        expect(state.form.name).toBe("");
        expect(state.form.status).toBe("draft");
        expect(state.form.variants).toHaveLength(1);
        expect(state.form.variants[0]?.sku).toBe("");
        expect(clearNotice).toHaveBeenCalledTimes(2);

        scope.stop();
    });
});
