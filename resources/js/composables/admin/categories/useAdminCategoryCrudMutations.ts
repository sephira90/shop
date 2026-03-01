import { ref, type Ref } from "vue";

import {
    createAdminCategory,
    deleteAdminCategory,
    updateAdminCategory,
} from "@/api/admin/categories";
import { executeAdminDeleteMutationPipeline } from "@/composables/admin/adminDeleteMutationPipeline";
import { resolvePageAfterLastItemRemoval } from "@/composables/admin/adminListPagination";
import { executeAdminSubmitMutationPipeline } from "@/composables/admin/adminSubmitMutationPipeline";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import type { AdminSubmitNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import type { AdminCategory } from "@/types/admin-categories";
import {
    buildCategoryMutationPayload,
    type CategoryFormState,
} from "@/validators/admin/categories";

interface AdminCategoriesCrudQueryAdapter {
    categories: Ref<AdminCategory[]>;
    page: Ref<number>;
    loadCategories: (targetPage?: number) => Promise<void>;
    loadParentOptions?: () => Promise<void>;
}

interface AdminCategoriesCrudFormAdapter {
    editingId: Ref<number | null>;
    form: CategoryFormState;
    resetFormKeepNotice: () => void;
}

interface UseAdminCategoryCrudMutationsOptions {
    query: AdminCategoriesCrudQueryAdapter;
    formState: AdminCategoriesCrudFormAdapter;
    executeMutation: ExecuteAdminMutation;
    notice: AdminSubmitNoticeAdapter;
    canDeleteCategories: Ref<boolean>;
    uiEffects: AdminUiEffectsAdapter;
}

export const useAdminCategoryCrudMutations = ({
    query,
    formState,
    executeMutation,
    notice,
    canDeleteCategories,
    uiEffects,
}: UseAdminCategoryCrudMutationsOptions) => {
    const isSubmitting = ref(false);
    const isDeletingId = ref<number | null>(null);

    const submitCategory = async (): Promise<void> => {
        await executeAdminSubmitMutationPipeline({
            executeMutation,
            setPending: (pending) => {
                isSubmitting.value = pending;
            },
            errorMessage: "Unable to save category.",
            buildPayload: () => buildCategoryMutationPayload(formState.form),
            editingId: formState.editingId.value,
            runCreate: async (payload) => {
                await createAdminCategory(payload);
            },
            runUpdate: async (id, payload) => {
                await updateAdminCategory(id, payload);
            },
            showSuccess: notice.showSuccess,
            successMessages: {
                create: "Category created successfully.",
                update: "Category updated successfully.",
            },
            onCreateSuccess: () => {
                query.page.value = 1;
            },
            onSuccess: async () => {
                await query.loadCategories(query.page.value);
                formState.resetFormKeepNotice();
                await query.loadParentOptions?.();
            },
        });
    };

    const removeCategory = async (category: AdminCategory): Promise<void> => {
        await executeAdminDeleteMutationPipeline<AdminCategory>({
            item: category,
            executeMutation,
            permission: {
                isAllowed: canDeleteCategories.value,
                deniedMessage: "Only admin can delete categories.",
                showDenied: notice.showError,
            },
            confirm: uiEffects.confirm,
            confirmMessage: (item) => `Delete category "${item.name}"?`,
            setPending: (pending) => {
                isDeletingId.value = pending ? category.id : null;
            },
            errorMessage: "Unable to delete category.",
            runDelete: async (item) => {
                await deleteAdminCategory(item.id);
            },
            showSuccess: notice.showSuccess,
            successMessage: "Category deleted.",
            onDeleted: async (item) => {
                const nextPage = resolvePageAfterLastItemRemoval({
                    currentPage: query.page.value,
                    visibleItemsCount: query.categories.value.length,
                });
                await query.loadCategories(nextPage);
                if (formState.editingId.value === item.id) {
                    formState.resetFormKeepNotice();
                }
                await query.loadParentOptions?.();
            },
        });
    };

    return {
        isSubmitting,
        isDeletingId,
        submitCategory,
        removeCategory,
    };
};
