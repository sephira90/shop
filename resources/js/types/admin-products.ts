import type { ListResponse } from '@/api/response';

export type ProductStatus = 'draft' | 'active' | 'archived';

export interface AdminProductCategory {
    id: number;
    name: string;
    slug: string;
}

export interface ProductVariantInventory {
    quantity: number | null;
    reserved_quantity: number | null;
    low_stock_threshold?: number | null;
}

export interface ProductVariant {
    id: number;
    sku: string;
    name: string;
    attributes: Record<string, unknown> | null;
    price: number;
    compare_at_price: number | null;
    currency: string;
    is_active: boolean;
    inventory: ProductVariantInventory | null;
}

export interface AdminProduct {
    id: number;
    sku: string;
    name: string;
    slug: string;
    short_description: string | null;
    description: string | null;
    status: ProductStatus;
    is_featured: boolean;
    brand: string | null;
    weight_grams: number | null;
    category: AdminProductCategory | null;
    meta: {
        title: string | null;
        description: string | null;
    };
    variants: ProductVariant[];
    published_at: string | null;
}

export type ProductListResponse = ListResponse<AdminProduct>;

export interface AdminProductListParams {
    page?: number;
    per_page?: number;
    q?: string;
    status?: ProductStatus;
    category_id?: number;
}

export interface ProductVariantForm {
    local_id: number;
    id: number | null;
    sku: string;
    name: string;
    price: string;
    compare_at_price: string;
    currency: string;
    is_active: boolean;
    attributes_json: string;
    inventory_quantity: string;
    inventory_reserved_quantity: string;
    inventory_low_stock_threshold: string;
}

export interface ProductMutationPayload {
    sku: string;
    name: string;
    slug: string | null;
    short_description: string | null;
    description: string | null;
    status: ProductStatus;
    is_featured: boolean;
    category_id: number | null;
    brand: string | null;
    weight_grams: number | null;
    meta_title: string | null;
    meta_description: string | null;
    published_at: string | null;
    variants?: Array<Record<string, unknown>>;
}
