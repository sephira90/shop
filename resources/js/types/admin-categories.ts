import type { ListResponse } from '@/api/response';

export interface AdminCategory {
    id: number;
    parent_id: number | null;
    name: string;
    slug: string;
    description: string | null;
    meta_title: string | null;
    meta_description: string | null;
    is_active: boolean;
    sort_order: number;
    parent: {
        id: number;
        name: string;
        slug: string;
    } | null;
    children_count: number;
    products_count: number;
}

export type CategoryListResponse = ListResponse<AdminCategory>;
export type CategoryStatusFilter = 'all' | 'active' | 'inactive';

export interface AdminCategoryListParams {
    page?: number;
    per_page?: number;
    q?: string;
    is_active?: boolean;
}

export interface CategoryMutationPayload {
    parent_id: number | null;
    name: string;
    slug: string | null;
    description: string | null;
    meta_title: string | null;
    meta_description: string | null;
    is_active: boolean;
    sort_order: number;
}
