import type { AdminProductListParams } from "@/types/admin-products";

export interface AdminProductFilters {
    searchQuery: string;
}

export const buildAdminProductListParams = (
    page: number,
    filters: AdminProductFilters,
): AdminProductListParams => {
    const params: AdminProductListParams = { page };
    const query = filters.searchQuery.trim();

    if (query !== "") {
        params.q = query;
    }

    return params;
};
