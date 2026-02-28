import { computed } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import type { AdminQueryNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { AdminProduct } from "@/types/admin-products";

import { useAdminProductCategoriesState } from "./useAdminProductCategoriesState";
import { useAdminProductsFilterState } from "./useAdminProductsFilterState";
import { useAdminProductsListState } from "./useAdminProductsListState";

export const useAdminProductsQuery = (
    notice: AdminQueryNoticeAdapter,
    routeSync?: AdminRouteSyncOptions,
) => {
    const filterState = useAdminProductsFilterState(routeSync);
    const { products, page, isLoading, meta, loadProducts } = useAdminProductsListState({
        notice,
        filterState,
        routeSync,
    });
    const { categories, isLoadingCategories, loadCategories } =
        useAdminProductCategoriesState(notice);

    const filteredProducts = computed<AdminProduct[]>(() => products.value);

    return {
        products,
        page,
        isLoading,
        meta,
        searchQuery: filterState.searchQuery,
        filteredProducts,
        loadProducts,
        categories,
        isLoadingCategories,
        loadCategories,
    };
};
