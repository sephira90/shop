import { apiClient } from "@/api/client";
import { normalizeListResponse } from "@/api/response";
import { assertAdminCategoryWireDto } from "@/contracts/api/v1/assertions/admin-categories";
import { mapAdminCategoryListFromApi, toCategoryMutationDto } from "@/mappers/admin/categories";
import type {
    AdminCategoryListParams,
    CategoryListResponse,
    CategoryMutationPayload,
} from "@/types/admin-categories";

interface ApiListRequestOptions {
    signal?: AbortSignal;
}

export const listAdminCategories = async (
    params: AdminCategoryListParams,
    options?: ApiListRequestOptions,
): Promise<CategoryListResponse> => {
    const { data } = await apiClient.get("/admin/categories", {
        params,
        signal: options?.signal,
    });

    const response = normalizeListResponse(data);

    return {
        data: mapAdminCategoryListFromApi(
            response.data.map((item) => assertAdminCategoryWireDto(item)),
        ),
        meta: response.meta,
    };
};

export const createAdminCategory = async (payload: CategoryMutationPayload): Promise<void> => {
    await apiClient.post("/admin/categories", toCategoryMutationDto(payload));
};

export const updateAdminCategory = async (
    categoryId: number,
    payload: CategoryMutationPayload,
): Promise<void> => {
    await apiClient.put(`/admin/categories/${categoryId}`, toCategoryMutationDto(payload));
};

export const deleteAdminCategory = async (categoryId: number): Promise<void> => {
    await apiClient.delete(`/admin/categories/${categoryId}`);
};
