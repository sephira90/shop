import { apiClient } from '@/api/client';
import { normalizeListResponse } from '@/api/response';
import { mapAdminCategoryListFromApi, toCategoryMutationDto } from '@/mappers/admin/categories';
import type { AdminCategoryListParams, CategoryListResponse, CategoryMutationPayload } from '@/types/admin-categories';

export const listAdminCategories = async (params: AdminCategoryListParams): Promise<CategoryListResponse> => {
    const { data } = await apiClient.get('/admin/categories', {
        params,
    });

    const response = normalizeListResponse<unknown>(data);

    return {
        data: mapAdminCategoryListFromApi(response.data),
        meta: response.meta,
    };
};

export const createAdminCategory = async (payload: CategoryMutationPayload): Promise<void> => {
    await apiClient.post('/admin/categories', toCategoryMutationDto(payload));
};

export const updateAdminCategory = async (categoryId: number, payload: CategoryMutationPayload): Promise<void> => {
    await apiClient.put(`/admin/categories/${categoryId}`, toCategoryMutationDto(payload));
};

export const deleteAdminCategory = async (categoryId: number): Promise<void> => {
    await apiClient.delete(`/admin/categories/${categoryId}`);
};
