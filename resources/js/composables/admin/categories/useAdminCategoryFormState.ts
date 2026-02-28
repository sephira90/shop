import { reactive, ref } from "vue";

import type { AdminClearNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { AdminCategory } from "@/types/admin-categories";
import { createCategoryFormState, type CategoryFormState } from "@/validators/admin/categories";

interface UseAdminCategoryFormStateOptions extends AdminClearNoticeAdapter {
    scrollToTop: () => void;
}

export const useAdminCategoryFormState = ({
    clearNotice,
    scrollToTop,
}: UseAdminCategoryFormStateOptions) => {
    const editingId = ref<number | null>(null);
    const form = reactive<CategoryFormState>(createCategoryFormState());

    const clearFormState = (): void => {
        editingId.value = null;
        Object.assign(form, createCategoryFormState());
    };

    const resetForm = (): void => {
        clearFormState();
        clearNotice();
    };

    const resetFormKeepNotice = (): void => {
        clearFormState();
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
        clearNotice();
        scrollToTop();
    };

    return {
        editingId,
        form,
        resetForm,
        resetFormKeepNotice,
        startEdit,
    };
};
