import { reactive, ref, type Ref } from "vue";

import {
    createAdminCategory,
    deleteAdminCategory,
    updateAdminCategory,
} from "@/api/admin/categories";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import type { AdminCategory } from "@/types/admin-categories";
import {
    buildCategoryMutationPayload,
    createCategoryFormState,
    type CategoryFormState,
} from "@/validators/admin/categories";

import type { useAdminCategoriesQuery } from "./useAdminCategoriesQuery";

interface AdminCategoriesMutationNoticeAdapter {
    clearNotice: () => void;
    showSuccess: (message: string) => void;
    showError: (message: string) => void;
}

interface UseAdminCategoriesMutationsOptions {
    query: ReturnType<typeof useAdminCategoriesQuery>;
    executeMutation: ExecuteAdminMutation;
    notice: AdminCategoriesMutationNoticeAdapter;
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
    const isSubmitting = ref(false);
    const isDeletingId = ref<number | null>(null);
    const editingId = ref<number | null>(null);
    const form = reactive<CategoryFormState>(createCategoryFormState());

    const clearFormState = (): void => {
        editingId.value = null;
        Object.assign(form, createCategoryFormState());
    };

    const resetForm = (): void => {
        clearFormState();
        notice.clearNotice();
    };

    const resetFormKeepNotice = (): void => {
        clearFormState();
    };

    const submitCategory = async (): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                isSubmitting.value = pending;
            },
            errorMessage: "Unable to save category.",
            run: async () => {
                const payload = buildCategoryMutationPayload(form);

                if (editingId.value) {
                    await updateAdminCategory(editingId.value, payload);
                    notice.showSuccess("Category updated successfully.");
                } else {
                    await createAdminCategory(payload);
                    notice.showSuccess("Category created successfully.");
                    query.page.value = 1;
                }

                await query.loadCategories(query.page.value);
                resetFormKeepNotice();
            },
        });
    };

    const startEdit = (category: AdminCategory): void => {
        editingId.value = category.id;
        form.parent_id = category.parent_id !== null ? String(category.parent_id) : "";
        form.name = category.name;
        form.slug = category.slug;
        form.description = category.description ?? "";
        form.meta_title = category.meta_title ?? "";
        form.meta_description = category.meta_description ?? "";
        form.is_active = category.is_active;
        form.sort_order = String(category.sort_order);
        notice.clearNotice();
        uiEffects.scrollToTop();
    };

    const removeCategory = async (category: AdminCategory): Promise<void> => {
        if (!canDeleteCategories.value) {
            notice.showError("Only admin can delete categories.");
            return;
        }

        const confirmed = await uiEffects.confirm(`Delete category "${category.name}"?`);
        if (!confirmed) {
            return;
        }

        await executeMutation<void>({
            setPending: (pending) => {
                isDeletingId.value = pending ? category.id : null;
            },
            errorMessage: "Unable to delete category.",
            run: async () => {
                await deleteAdminCategory(category.id);
                notice.showSuccess("Category deleted.");
                const nextPage =
                    query.categories.value.length === 1 && query.page.value > 1
                        ? query.page.value - 1
                        : query.page.value;
                await query.loadCategories(nextPage);
                if (editingId.value === category.id) {
                    resetFormKeepNotice();
                }
            },
        });
    };

    return {
        isSubmitting,
        isDeletingId,
        editingId,
        form,
        resetForm,
        submitCategory,
        startEdit,
        removeCategory,
    };
};
