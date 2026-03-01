import { ref } from "vue";

import { listAdminCategoryOptions } from "@/api/admin/categories";
import type { AdminQueryNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { AdminCategoryOption, AdminCategoryOptionListParams } from "@/types/admin-categories";

const normalizeExcludeId = (value: number | undefined): number | null => {
    return Number.isInteger(value) && value !== undefined && value > 0 ? value : null;
};

const applyExcludedCategoryFilter = (
    categories: AdminCategoryOption[],
    excludeId: number | null,
): AdminCategoryOption[] => {
    if (excludeId === null) {
        return categories;
    }

    return categories.filter((category) => category.id !== excludeId);
};

export const useAdminCategoryOptionsState = (notice: AdminQueryNoticeAdapter) => {
    const categoryOptions = ref<AdminCategoryOption[]>([]);
    const isLoadingCategoryOptions = ref(false);
    let latestRequestId = 0;

    const loadCategoryOptions = async (
        params: AdminCategoryOptionListParams = {},
    ): Promise<void> => {
        const requestId = ++latestRequestId;
        const excludeId = normalizeExcludeId(params.exclude_id);

        isLoadingCategoryOptions.value = true;

        try {
            const categories = await listAdminCategoryOptions(params);

            if (requestId !== latestRequestId) {
                return;
            }

            categoryOptions.value = applyExcludedCategoryFilter(categories, excludeId);
        } catch (error: unknown) {
            if (requestId !== latestRequestId) {
                return;
            }

            categoryOptions.value = [];
            notice.showApiError(error, "Unable to load category options.");
        } finally {
            if (requestId === latestRequestId) {
                isLoadingCategoryOptions.value = false;
            }
        }
    };

    return {
        categoryOptions,
        isLoadingCategoryOptions,
        loadCategoryOptions,
    };
};
