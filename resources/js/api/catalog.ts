import { apiClient } from "@/api/client";
import { extractData, normalizeListResponse } from "@/api/response";
import {
    assertCatalogProductWireDto,
    assertCatalogProductWireDtoList,
} from "@/contracts/api/v1/assertions/catalog";
import { mapCatalogProductFromApi, mapCatalogProductListFromApi } from "@/mappers/catalog";
import type {
    CatalogProduct,
    CatalogProductListParams,
    CatalogProductListResponse,
} from "@/types/catalog";

export const listCatalogProducts = async (
    params: CatalogProductListParams,
): Promise<CatalogProductListResponse> => {
    const { data } = await apiClient.get("/catalog/products", {
        params,
    });
    const response = normalizeListResponse(data);

    return {
        data: mapCatalogProductListFromApi(assertCatalogProductWireDtoList(response.data)),
        meta: response.meta,
    };
};

export const getCatalogProductBySlug = async (slug: string): Promise<CatalogProduct | null> => {
    const { data } = await apiClient.get(`/catalog/products/${slug}`);
    const response = extractData(data);

    return response ? mapCatalogProductFromApi(assertCatalogProductWireDto(response)) : null;
};
