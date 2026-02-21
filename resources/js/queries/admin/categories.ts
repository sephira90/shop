import type { AdminCategoryListParams, CategoryStatusFilter } from "@/types/admin-categories";

export interface AdminCategoryFilters {
    searchQuery: string;
    statusFilter: CategoryStatusFilter;
}

export const buildAdminCategoryListParams = (
    page: number,
    filters: AdminCategoryFilters,
): AdminCategoryListParams => {
    const params: AdminCategoryListParams = {
        page,
        per_page: 200,
    };
    const query = filters.searchQuery.trim();

    if (query !== "") {
        params.q = query;
    }

    if (filters.statusFilter !== "all") {
        params.is_active = filters.statusFilter === "active";
    }

    return params;
};
