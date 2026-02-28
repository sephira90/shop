import { ref, type Ref } from "vue";

import { createAdminProduct, deleteAdminProduct, updateAdminProduct } from "@/api/admin/products";
import { executeAdminDeleteMutationPipeline } from "@/composables/admin/adminDeleteMutationPipeline";
import { resolvePageAfterLastItemRemoval } from "@/composables/admin/adminListPagination";
import { executeAdminSubmitMutationPipeline } from "@/composables/admin/adminSubmitMutationPipeline";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import type { AdminSubmitNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import type { AdminProduct } from "@/types/admin-products";
import { buildProductMutationPayload, type ProductFormState } from "@/validators/admin/products";

interface AdminProductsCrudQueryAdapter {
    products: Ref<AdminProduct[]>;
    page: Ref<number>;
    loadProducts: (targetPage?: number) => Promise<void>;
}

interface AdminProductsCrudFormAdapter {
    editingId: Ref<number | null>;
    form: ProductFormState;
    resetFormKeepNotice: () => void;
}

interface UseAdminProductCrudMutationsOptions {
    query: AdminProductsCrudQueryAdapter;
    formState: AdminProductsCrudFormAdapter;
    executeMutation: ExecuteAdminMutation;
    notice: AdminSubmitNoticeAdapter;
    canDeleteProducts: Ref<boolean>;
    uiEffects: AdminUiEffectsAdapter;
}

export const useAdminProductCrudMutations = ({
    query,
    formState,
    executeMutation,
    notice,
    canDeleteProducts,
    uiEffects,
}: UseAdminProductCrudMutationsOptions) => {
    const isSubmitting = ref(false);
    const isDeletingId = ref<number | null>(null);

    const submitProduct = async (): Promise<void> => {
        await executeAdminSubmitMutationPipeline({
            executeMutation,
            setPending: (pending) => {
                isSubmitting.value = pending;
            },
            errorMessage: "Unable to save product.",
            buildPayload: () => buildProductMutationPayload(formState.form),
            editingId: formState.editingId.value,
            runCreate: async (payload) => {
                await createAdminProduct(payload);
            },
            runUpdate: async (id, payload) => {
                await updateAdminProduct(id, payload);
            },
            showSuccess: notice.showSuccess,
            successMessages: {
                create: "Product created successfully.",
                update: "Product updated successfully.",
            },
            onCreateSuccess: () => {
                query.page.value = 1;
            },
            onSuccess: async () => {
                await query.loadProducts(query.page.value);
                formState.resetFormKeepNotice();
            },
        });
    };

    const removeProduct = async (product: AdminProduct): Promise<void> => {
        await executeAdminDeleteMutationPipeline<AdminProduct>({
            item: product,
            executeMutation,
            permission: {
                isAllowed: canDeleteProducts.value,
                deniedMessage: "Only admin can delete products.",
                showDenied: notice.showError,
            },
            confirm: uiEffects.confirm,
            confirmMessage: (item) => `Delete product "${item.name}"?`,
            setPending: (pending) => {
                isDeletingId.value = pending ? product.id : null;
            },
            errorMessage: "Unable to delete product.",
            runDelete: async (item) => {
                await deleteAdminProduct(item.id);
            },
            showSuccess: notice.showSuccess,
            successMessage: "Product deleted.",
            onDeleted: async (item) => {
                const nextPage = resolvePageAfterLastItemRemoval({
                    currentPage: query.page.value,
                    visibleItemsCount: query.products.value.length,
                });
                await query.loadProducts(nextPage);
                if (formState.editingId.value === item.id) {
                    formState.resetFormKeepNotice();
                }
            },
        });
    };

    return {
        isSubmitting,
        isDeletingId,
        submitProduct,
        removeProduct,
    };
};
