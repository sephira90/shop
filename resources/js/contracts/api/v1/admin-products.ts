export type AdminProductStatusWireDto = "draft" | "active" | "archived";

export interface AdminProductCategoryWireDto {
    id: number;
    name: string;
    slug: string;
}

export interface AdminProductMetaWireDto {
    title: string | null;
    description: string | null;
}

export interface AdminProductVariantInventoryWireDto {
    quantity: number | null;
    reserved_quantity: number | null;
    available_quantity: number | null;
}

export interface AdminProductVariantWireDto {
    id: number;
    sku: string;
    name: string;
    attributes: Record<string, unknown> | null;
    price: number;
    compare_at_price: number | null;
    currency: string;
    is_active: boolean;
    inventory: AdminProductVariantInventoryWireDto | null;
}

export interface AdminProductWireDto {
    id: number;
    sku: string;
    name: string;
    slug: string;
    short_description: string | null;
    description: string | null;
    status: AdminProductStatusWireDto;
    is_featured: boolean;
    brand: string | null;
    weight_grams: number | null;
    category: AdminProductCategoryWireDto | null;
    meta: AdminProductMetaWireDto;
    variants: AdminProductVariantWireDto[];
    published_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface AdminProductVariantInventoryMutationRequestDto {
    quantity: number;
    reserved_quantity: number;
    low_stock_threshold: number;
}

export interface AdminProductVariantMutationRequestDto {
    id?: number;
    sku: string;
    name: string;
    attributes: Record<string, unknown>;
    price: number;
    compare_at_price: number | null;
    currency: string;
    is_active: boolean;
    inventory: AdminProductVariantInventoryMutationRequestDto;
}

export interface AdminProductMutationRequestDto {
    sku: string;
    name: string;
    slug: string | null;
    short_description: string | null;
    description: string | null;
    status: AdminProductStatusWireDto;
    is_featured: boolean;
    category_id: number | null;
    brand: string | null;
    weight_grams: number | null;
    meta_title: string | null;
    meta_description: string | null;
    published_at: string | null;
    variants?: AdminProductVariantMutationRequestDto[];
}
