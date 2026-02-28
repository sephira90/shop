import type { Ref } from "vue";

import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import type { AdminMutationNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

import type { useAdminProductsQuery } from "./useAdminProductsQuery";
import { useAdminProductCrudMutations } from "./useAdminProductCrudMutations";
import { useAdminProductFormState } from "./useAdminProductFormState";
import { useAdminProductPublishingMutations } from "./useAdminProductPublishingMutations";

interface UseAdminProductsMutationsOptions {
    query: ReturnType<typeof useAdminProductsQuery>;
    executeMutation: ExecuteAdminMutation;
    notice: AdminMutationNoticeAdapter;
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
    const formState = useAdminProductFormState({
        clearNotice: notice.clearNotice,
        scrollToTop: uiEffects.scrollToTop,
    });
    const crudMutations = useAdminProductCrudMutations({
        query,
        formState,
        executeMutation,
        notice: {
            showSuccess: notice.showSuccess,
            showError: notice.showError,
        },
        canDeleteProducts,
        uiEffects,
    });
    const publishingMutations = useAdminProductPublishingMutations({
        query,
        executeMutation,
        notice: {
            showSuccess: notice.showSuccess,
        },
    });

    return {
        isSubmitting: crudMutations.isSubmitting,
        isDeletingId: crudMutations.isDeletingId,
        isVisibilityUpdatingId: publishingMutations.isVisibilityUpdatingId,
        isRefreshingCatalogCache: publishingMutations.isRefreshingCatalogCache,
        editingId: formState.editingId,
        form: formState.form,
        resetForm: formState.resetForm,
        addVariant: formState.addVariant,
        removeVariant: formState.removeVariant,
        submitProduct: crudMutations.submitProduct,
        startEdit: formState.startEdit,
        removeProduct: crudMutations.removeProduct,
        refreshCatalogCache: publishingMutations.refreshCatalogCache,
        toggleCatalogVisibility: publishingMutations.toggleCatalogVisibility,
        isVisibleInCatalog: publishingMutations.isVisibleInCatalog,
    };
};
