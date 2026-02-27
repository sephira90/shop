import { apiClient } from "@/api/client";
import { extractData, normalizeListResponse } from "@/api/response";
import { assertAdminProductWireDto } from "@/contracts/api/v1/assertions/admin-products";
import {
    mapAdminProductFromApi,
    mapAdminProductListFromApi,
    toProductMutationDto,
} from "@/mappers/admin/products";
import type {
    AdminProduct,
    AdminProductListParams,
    ProductListResponse,
    ProductMutationPayload,
} from "@/types/admin-products";

interface ApiListRequestOptions {
    signal?: AbortSignal;
}

export const listAdminProducts = async (
    params: AdminProductListParams,
    options?: ApiListRequestOptions,
): Promise<ProductListResponse> => {
    const { data } = await apiClient.get("/admin/products", {
        params,
        signal: options?.signal,
    });

    const response = normalizeListResponse(data);

    return {
        data: mapAdminProductListFromApi(
            response.data.map((item) => assertAdminProductWireDto(item)),
        ),
        meta: response.meta,
    };
};

export const createAdminProduct = async (
    payload: ProductMutationPayload,
): Promise<AdminProduct | null> => {
    const { data } = await apiClient.post("/admin/products", toProductMutationDto(payload));
    const response = extractData(data);

    if (response === null) {
        return null;
    }

    return mapAdminProductFromApi(assertAdminProductWireDto(response));
};

export const updateAdminProduct = async (
    productId: number,
    payload: ProductMutationPayload,
): Promise<AdminProduct | null> => {
    const { data } = await apiClient.put(
        `/admin/products/${productId}`,
        toProductMutationDto(payload),
    );
    const response = extractData(data);

    if (response === null) {
        return null;
    }

    return mapAdminProductFromApi(assertAdminProductWireDto(response));
};

export const deleteAdminProduct = async (productId: number): Promise<void> => {
    await apiClient.delete(`/admin/products/${productId}`);
};

export const refreshAdminCatalogCache = async (): Promise<number> => {
    const { data } = await apiClient.post("/admin/cache/refresh-catalog");
    const payload = extractData<{ catalog_version?: number }>(data);

    return Number(payload?.catalog_version ?? 0);
};
