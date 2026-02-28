import { reactive, ref } from "vue";

import type { AdminClearNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { AdminProduct, ProductVariant, ProductVariantForm } from "@/types/admin-products";
import { normalizeDatetimeForInput } from "@/utils/datetime";
import { createProductFormState, type ProductFormState } from "@/validators/admin/products";

interface UseAdminProductFormStateOptions extends AdminClearNoticeAdapter {
    scrollToTop: () => void;
}

export const useAdminProductFormState = ({
    clearNotice,
    scrollToTop,
}: UseAdminProductFormStateOptions) => {
    const editingId = ref<number | null>(null);
    let nextVariantLocalId = 1;

    const createEmptyVariantForm = (): ProductVariantForm => {
        return {
            local_id: nextVariantLocalId++,
            id: null,
            sku: "",
            name: "",
            price: "0.00",
            compare_at_price: "",
            currency: "USD",
            is_active: true,
            attributes_json: "{}",
            inventory_quantity: "0",
            inventory_reserved_quantity: "0",
            inventory_low_stock_threshold: "3",
        };
    };

    const formatVariantAttributes = (attributes: Record<string, unknown> | null): string => {
        if (!attributes || typeof attributes !== "object" || Array.isArray(attributes)) {
            return "{}";
        }

        return JSON.stringify(attributes, null, 2);
    };

    const mapVariantToForm = (variant: ProductVariant): ProductVariantForm => {
        return {
            local_id: nextVariantLocalId++,
            id: variant.id,
            sku: variant.sku,
            name: variant.name,
            price: String(variant.price),
            compare_at_price:
                variant.compare_at_price !== null ? String(variant.compare_at_price) : "",
            currency: variant.currency,
            is_active: variant.is_active,
            attributes_json: formatVariantAttributes(variant.attributes),
            inventory_quantity: String(variant.inventory?.quantity ?? 0),
            inventory_reserved_quantity: String(variant.inventory?.reserved_quantity ?? 0),
            inventory_low_stock_threshold: String(variant.inventory?.low_stock_threshold ?? 3),
        };
    };

    const form = reactive<ProductFormState>(createProductFormState([createEmptyVariantForm()]));

    const clearFormState = (): void => {
        editingId.value = null;
        Object.assign(form, createProductFormState([createEmptyVariantForm()]));
    };

    const resetForm = (): void => {
        clearFormState();
        clearNotice();
    };

    const resetFormKeepNotice = (): void => {
        clearFormState();
    };

    const addVariant = (): void => {
        form.variants.push(createEmptyVariantForm());
    };

    const removeVariant = (index: number): void => {
        if (form.variants.length <= 1) {
            return;
        }

        form.variants.splice(index, 1);
    };

    const startEdit = (product: AdminProduct): void => {
        editingId.value = product.id;
        form.sku = product.sku;
        form.name = product.name;
        form.slug = product.slug;
        form.short_description = product.short_description ?? "";
        form.description = product.description ?? "";
        form.status = product.status;
        form.is_featured = product.is_featured;
        form.category_id = product.category ? String(product.category.id) : "";
        form.brand = product.brand ?? "";
        form.weight_grams = product.weight_grams !== null ? String(product.weight_grams) : "";
        form.meta_title = product.meta.title ?? "";
        form.meta_description = product.meta.description ?? "";
        form.published_at = normalizeDatetimeForInput(product.published_at);
        form.variants =
            product.variants.length > 0
                ? product.variants.map((variant) => mapVariantToForm(variant))
                : [createEmptyVariantForm()];
        clearNotice();
        scrollToTop();
    };

    return {
        editingId,
        form,
        resetForm,
        resetFormKeepNotice,
        addVariant,
        removeVariant,
        startEdit,
    };
};
