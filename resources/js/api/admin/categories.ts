import { apiClient } from "@/api/client";
import { extractData, normalizeListResponse, ApiContractError } from "@/api/response";
import {
    assertAdminCategoryOptionWireDto,
    assertAdminCategoryWireDto,
} from "@/contracts/api/v1/assertions/admin-categories";
import {
    mapAdminCategoryListFromApi,
    mapAdminCategoryOptionListFromApi,
    toCategoryMutationDto,
} from "@/mappers/admin/categories";
import type {
    AdminCategoryOptionListParams,
    AdminCategoryListParams,
    AdminCategoryOption,
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

export const listAdminCategoryOptions = async (
    params: AdminCategoryOptionListParams = {},
): Promise<AdminCategoryOption[]> => {
    const { data } = await apiClient.get("/admin/categories/options", {
        params,
    });

    const response = extractData<object[]>(data);

    if (!Array.isArray(response)) {
        throw new ApiContractError("Admin category options response `data` must be an array.");
    }

    return mapAdminCategoryOptionListFromApi(
        response.map((item) => assertAdminCategoryOptionWireDto(item)),
    );
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
