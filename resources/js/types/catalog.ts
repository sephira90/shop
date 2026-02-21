import type { ListResponse } from "@/api/response";

export type CatalogSort = "newest" | "price_asc" | "price_desc" | "name_asc";

export interface CatalogProductVariant {
    id: number;
    sku: string;
    name: string;
    price: number;
    currency: string;
    is_active: boolean;
}

export interface CatalogProduct {
    id: number;
    name: string;
    slug: string;
    short_description: string | null;
    description: string | null;
    variants: CatalogProductVariant[];
}

export type CatalogProductListResponse = ListResponse<CatalogProduct>;

export interface CatalogProductListParams {
    page?: number;
    per_page?: number;
    q?: string;
    sort?: CatalogSort;
}
