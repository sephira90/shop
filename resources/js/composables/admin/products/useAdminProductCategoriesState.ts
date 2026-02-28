import { ref } from "vue";

import { listAdminCategories } from "@/api/admin/categories";
import type { AdminQueryNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { AdminCategory } from "@/types/admin-categories";
import type { AdminProductCategory } from "@/types/admin-products";

const toCategoryOption = (category: AdminCategory): AdminProductCategory => ({
    id: category.id,
    name: category.name,
    slug: category.slug,
});

export const useAdminProductCategoriesState = (notice: AdminQueryNoticeAdapter) => {
    const categories = ref<AdminProductCategory[]>([]);
    const isLoadingCategories = ref(false);

    const loadCategories = async (): Promise<void> => {
        notice.clearNotice();
        isLoadingCategories.value = true;

        try {
            const collected: AdminProductCategory[] = [];
            let currentPage = 1;

            while (true) {
                const response = await listAdminCategories({
                    page: currentPage,
                    per_page: 200,
                });

                collected.push(...response.data.map((category) => toCategoryOption(category)));

                if (response.meta.current_page >= response.meta.last_page) {
                    break;
                }

                currentPage += 1;
            }

            categories.value = collected.sort((left, right) => left.name.localeCompare(right.name));
        } catch (error: unknown) {
            categories.value = [];
            notice.showApiError(error, "Unable to load categories for product form.");
        } finally {
            isLoadingCategories.value = false;
        }
    };

    return {
        categories,
        isLoadingCategories,
        loadCategories,
    };
};
