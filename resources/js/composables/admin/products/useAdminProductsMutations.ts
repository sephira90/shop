import { reactive, ref, type Ref } from "vue";

import {
    createAdminProduct,
    deleteAdminProduct,
    refreshAdminCatalogCache,
    updateAdminProduct,
} from "@/api/admin/products";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import type { AdminProduct, ProductVariant, ProductVariantForm } from "@/types/admin-products";
import { normalizeDatetimeForInput } from "@/utils/datetime";
import {
    buildProductMutationPayload,
    buildProductMutationPayloadFromProduct,
    createProductFormState,
    type ProductFormState,
} from "@/validators/admin/products";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

import type { useAdminProductsQuery } from "./useAdminProductsQuery";

interface AdminProductsMutationNoticeAdapter {
    clearNotice: () => void;
    showSuccess: (message: string) => void;
    showError: (message: string) => void;
}

interface UseAdminProductsMutationsOptions {
    query: ReturnType<typeof useAdminProductsQuery>;
    executeMutation: ExecuteAdminMutation;
    notice: AdminProductsMutationNoticeAdapter;
    canDeleteProducts: Ref<boolean>;
    uiEffects: AdminUiEffectsAdapter;
}

export const useAdminProductsMutations = ({
    query,
    executeMutation,
    notice,
    canDeleteProducts,
    uiEffects,
}: UseAdminProductsMutationsOptions) => {
    const isSubmitting = ref(false);
    const isDeletingId = ref<number | null>(null);
    const isVisibilityUpdatingId = ref<number | null>(null);
    const isRefreshingCatalogCache = ref(false);
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
        notice.clearNotice();
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

    const isVisibleInCatalog = (product: AdminProduct): boolean => {
        return product.status === "active" && product.published_at !== null;
    };

    const submitProduct = async (): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                isSubmitting.value = pending;
            },
            errorMessage: "Unable to save product.",
            run: async () => {
                const payload = buildProductMutationPayload(form);

                if (editingId.value) {
                    await updateAdminProduct(editingId.value, payload);
                    notice.showSuccess("Product updated successfully.");
                } else {
                    await createAdminProduct(payload);
                    notice.showSuccess("Product created successfully.");
                    query.page.value = 1;
                }

                await query.loadProducts(query.page.value);
                resetFormKeepNotice();
            },
        });
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
        notice.clearNotice();
        uiEffects.scrollToTop();
    };

    const removeProduct = async (product: AdminProduct): Promise<void> => {
        if (!canDeleteProducts.value) {
            notice.showError("Only admin can delete products.");
            return;
        }

        const confirmed = await uiEffects.confirm(`Delete product "${product.name}"?`);
        if (!confirmed) {
            return;
        }

        await executeMutation<void>({
            setPending: (pending) => {
                isDeletingId.value = pending ? product.id : null;
            },
            errorMessage: "Unable to delete product.",
            run: async () => {
                await deleteAdminProduct(product.id);
                notice.showSuccess("Product deleted.");
                const nextPage =
                    query.products.value.length === 1 && query.page.value > 1
                        ? query.page.value - 1
                        : query.page.value;
                await query.loadProducts(nextPage);
                if (editingId.value === product.id) {
                    resetFormKeepNotice();
                }
            },
        });
    };

    const refreshCatalogCache = async (): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                isRefreshingCatalogCache.value = pending;
            },
            errorMessage: "Unable to refresh catalog cache.",
            run: async () => {
                const nextVersion = await refreshAdminCatalogCache();

                notice.showSuccess(
                    nextVersion > 0
                        ? `Catalog cache refreshed (version ${nextVersion}). Storefront browser cache may take up to 60 seconds.`
                        : "Catalog cache refreshed. Storefront browser cache may take up to 60 seconds.",
                );
            },
        });
    };

    const toggleCatalogVisibility = async (product: AdminProduct): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                isVisibilityUpdatingId.value = pending ? product.id : null;
            },
            errorMessage: "Unable to change catalog visibility.",
            run: async () => {
                const currentlyVisible = isVisibleInCatalog(product);
                const payload = buildProductMutationPayloadFromProduct(product);
                payload.status = currentlyVisible ? "draft" : "active";
                payload.published_at = currentlyVisible ? null : new Date().toISOString();

                await updateAdminProduct(product.id, payload);

                notice.showSuccess(
                    currentlyVisible
                        ? "Product hidden from catalog."
                        : "Product published to catalog. Public cache may refresh within 60 seconds.",
                );

                await query.loadProducts(query.page.value);
            },
        });
    };

    return {
        isSubmitting,
        isDeletingId,
        isVisibilityUpdatingId,
        isRefreshingCatalogCache,
        editingId,
        form,
        resetForm,
        addVariant,
        removeVariant,
        submitProduct,
        startEdit,
        removeProduct,
        refreshCatalogCache,
        toggleCatalogVisibility,
        isVisibleInCatalog,
    };
};
