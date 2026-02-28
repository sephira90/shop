import type { Ref } from "vue";

import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import type { AdminMutationNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

import type { useAdminCategoriesQuery } from "./useAdminCategoriesQuery";
import { useAdminCategoryCrudMutations } from "./useAdminCategoryCrudMutations";
import { useAdminCategoryFormState } from "./useAdminCategoryFormState";

interface UseAdminCategoriesMutationsOptions {
    query: ReturnType<typeof useAdminCategoriesQuery>;
    executeMutation: ExecuteAdminMutation;
    notice: AdminMutationNoticeAdapter;
    canDeleteCategories: Ref<boolean>;
    uiEffects: AdminUiEffectsAdapter;
}

export const useAdminCategoriesMutations = ({
    query,
    executeMutation,
    notice,
    canDeleteCategories,
    uiEffects,
}: UseAdminCategoriesMutationsOptions) => {
    const formState = useAdminCategoryFormState({
        clearNotice: notice.clearNotice,
        scrollToTop: uiEffects.scrollToTop,
    });
    const crudMutations = useAdminCategoryCrudMutations({
        query,
        formState,
        executeMutation,
        notice: {
            showSuccess: notice.showSuccess,
            showError: notice.showError,
        },
        canDeleteCategories,
        uiEffects,
    });

    return {
        isSubmitting: crudMutations.isSubmitting,
        isDeletingId: crudMutations.isDeletingId,
        editingId: formState.editingId,
        form: formState.form,
        resetForm: formState.resetForm,
        submitCategory: crudMutations.submitCategory,
        startEdit: formState.startEdit,
        removeCategory: crudMutations.removeCategory,
    };
};
