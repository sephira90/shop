import { computed, reactive, ref } from 'vue';

import {
    createAdminCategory,
    deleteAdminCategory,
    listAdminCategories,
    updateAdminCategory,
} from '@/api/admin/categories';
import { useAdminMutation } from '@/composables/useAdminMutation';
import { useAdminNotice } from '@/composables/useAdminNotice';
import { useServerPaginatedList } from '@/composables/useServerPaginatedList';
import { buildAdminCategoryListParams } from '@/queries/admin/categories';
import { useAuthStore } from '@/stores/auth';
import type { AdminCategory, AdminCategoryListParams, CategoryStatusFilter } from '@/types/admin-categories';
import {
    buildCategoryMutationPayload,
    createCategoryFormState,
    type CategoryFormState,
} from '@/validators/admin/categories';

export const useAdminCategories = () => {
    const authStore = useAuthStore();
    const searchQuery = ref('');
    const statusFilter = ref<CategoryStatusFilter>('all');
    const isSubmitting = ref(false);
    const isDeletingId = ref<number | null>(null);
    const editingId = ref<number | null>(null);
    const canDeleteCategories = computed<boolean>(() => authStore.hasRole('admin'));
    const { notice, clearNotice, showSuccess, showError, showApiError } = useAdminNotice();
    const { executeMutation } = useAdminMutation({
        clearNotice,
        showApiError,
    });
    const form = reactive<CategoryFormState>(createCategoryFormState());

    const {
        items: categories,
        page,
        isLoading,
        meta,
        load: loadCategories,
    } = useServerPaginatedList<AdminCategory, AdminCategoryListParams>({
        buildParams: (targetPage) =>
            buildAdminCategoryListParams(targetPage, {
                searchQuery: searchQuery.value,
                statusFilter: statusFilter.value,
            }),
        fetchPage: listAdminCategories,
        filterSource: () => [searchQuery.value, statusFilter.value],
        debounceMs: 300,
        resetOnError: true,
        onLoading: () => {
            clearNotice();
        },
        onError: (error: unknown) => {
            showApiError(error, 'Unable to load categories.');
        },
    });

    const filteredCategories = computed<AdminCategory[]>(() => categories.value);

    const parentOptions = computed<AdminCategory[]>(() => {
        return categories.value.filter((category) => category.id !== editingId.value);
    });

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

    const submitCategory = async (): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                isSubmitting.value = pending;
            },
            errorMessage: 'Unable to save category.',
            run: async () => {
                const payload = buildCategoryMutationPayload(form);

                if (editingId.value) {
                    await updateAdminCategory(editingId.value, payload);
                    showSuccess('Category updated successfully.');
                } else {
                    await createAdminCategory(payload);
                    showSuccess('Category created successfully.');
                    page.value = 1;
                }

                await loadCategories(page.value);
                resetFormKeepNotice();
            },
        });
    };

    const startEdit = (category: AdminCategory): void => {
        editingId.value = category.id;
        form.parent_id = category.parent_id !== null ? String(category.parent_id) : '';
        form.name = category.name;
        form.slug = category.slug;
        form.description = category.description ?? '';
        form.meta_title = category.meta_title ?? '';
        form.meta_description = category.meta_description ?? '';
        form.is_active = category.is_active;
        form.sort_order = String(category.sort_order);
        clearNotice();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const removeCategory = async (category: AdminCategory): Promise<void> => {
        if (!canDeleteCategories.value) {
            showError('Only admin can delete categories.');
            return;
        }

        if (!window.confirm(`Delete category "${category.name}"?`)) {
            return;
        }

        await executeMutation<void>({
            setPending: (pending) => {
                isDeletingId.value = pending ? category.id : null;
            },
            errorMessage: 'Unable to delete category.',
            run: async () => {
                await deleteAdminCategory(category.id);
                showSuccess('Category deleted.');
                const nextPage = categories.value.length === 1 && page.value > 1 ? page.value - 1 : page.value;
                await loadCategories(nextPage);
                if (editingId.value === category.id) {
                    resetFormKeepNotice();
                }
            },
        });
    };

    return {
        categories,
        page,
        isLoading,
        isSubmitting,
        isDeletingId,
        editingId,
        searchQuery,
        statusFilter,
        canDeleteCategories,
        meta,
        notice,
        form,
        filteredCategories,
        parentOptions,
        resetForm,
        loadCategories,
        submitCategory,
        startEdit,
        removeCategory,
    };
};
