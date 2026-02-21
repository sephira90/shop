import type { PromotionListParams, PromotionStatusFilter } from '@/types/admin-promotions';

export interface AdminPromotionFilters {
    searchQuery: string;
    statusFilter: PromotionStatusFilter;
}

export const buildAdminPromotionListParams = (page: number, filters: AdminPromotionFilters): PromotionListParams => {
    const params: PromotionListParams = { page };
    const query = filters.searchQuery.trim();

    if (query !== '') {
        params.q = query;
    }

    if (filters.statusFilter !== 'all') {
        params.is_active = filters.statusFilter === 'active';
    }

    return params;
};
