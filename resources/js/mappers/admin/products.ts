import type {
    AdminProductMutationRequestDto,
    AdminProductWireDto,
} from "@/contracts/api/v1/admin-products";
import type {
    AdminProduct,
    AdminProductCategory,
    ProductMutationPayload,
    ProductStatus,
    ProductVariant,
    ProductVariantInventory,
} from "@/types/admin-products";

const mapProductStatus = (value: AdminProductWireDto["status"]): ProductStatus => {
    if (value === "active" || value === "archived") {
        return value;
    }

    return "draft";
};

const mapProductCategory = (
    value: AdminProductWireDto["category"],
): AdminProductCategory | null => {
    if (value === null || value.id <= 0) {
        return null;
    }

    return {
        id: value.id,
        name: value.name,
        slug: value.slug,
    };
};

const mapVariantInventory = (
    value: AdminProductWireDto["variants"][number]["inventory"],
): ProductVariantInventory | null => {
    if (value === null) {
        return null;
    }

    return {
        quantity: value.quantity,
        reserved_quantity: value.reserved_quantity,
    };
};

const mapVariantAttributes = (
    value: AdminProductWireDto["variants"][number]["attributes"],
): ProductVariant["attributes"] => {
    if (value === null || Object.keys(value).length === 0) {
        return null;
    }

    return value;
};

const mapProductVariant = (value: AdminProductWireDto["variants"][number]): ProductVariant => {
    return {
        id: value.id,
        sku: value.sku,
        name: value.name,
        attributes: mapVariantAttributes(value.attributes),
        price: value.price,
        compare_at_price: value.compare_at_price,
        currency: value.currency,
        is_active: value.is_active,
        inventory: mapVariantInventory(value.inventory),
    };
};

export const mapAdminProductFromApi = (value: AdminProductWireDto): AdminProduct => {
    return {
        id: value.id,
        sku: value.sku,
        name: value.name,
        slug: value.slug,
        short_description: value.short_description,
        description: value.description,
        status: mapProductStatus(value.status),
        is_featured: value.is_featured,
        brand: value.brand,
        weight_grams: value.weight_grams,
        category: mapProductCategory(value.category),
        meta: {
            title: value.meta.title,
            description: value.meta.description,
        },
        variants: value.variants.map((variant) => mapProductVariant(variant)),
        published_at: value.published_at,
    };
};

export const mapAdminProductListFromApi = (value: AdminProductWireDto[]): AdminProduct[] => {
    return value.map((item) => mapAdminProductFromApi(item));
};

const normalizeVariantMutation = (
    variant: NonNullable<ProductMutationPayload["variants"]>[number],
): NonNullable<AdminProductMutationRequestDto["variants"]>[number] => {
    const quantity = Math.max(0, Math.trunc(variant.inventory.quantity));
    const reservedQuantity = Math.max(0, Math.trunc(variant.inventory.reserved_quantity));
    const lowStockThreshold = Math.max(0, Math.trunc(variant.inventory.low_stock_threshold));

    return {
        ...(variant.id !== undefined ? { id: variant.id } : {}),
        sku: variant.sku.trim(),
        name: variant.name.trim(),
        attributes: variant.attributes,
        price: variant.price,
        compare_at_price: variant.compare_at_price,
        currency: variant.currency.trim().toUpperCase(),
        is_active: variant.is_active,
        inventory: {
            quantity,
            reserved_quantity: Math.min(reservedQuantity, quantity),
            low_stock_threshold: lowStockThreshold,
        },
    };
};

export const toProductMutationDto = (
    payload: ProductMutationPayload,
): AdminProductMutationRequestDto => {
    return {
        sku: payload.sku.trim(),
        name: payload.name.trim(),
        slug: payload.slug,
        short_description: payload.short_description,
        description: payload.description,
        status: payload.status,
        is_featured: payload.is_featured,
        category_id: payload.category_id,
        brand: payload.brand,
        weight_grams: payload.weight_grams,
        meta_title: payload.meta_title,
        meta_description: payload.meta_description,
        published_at: payload.published_at,
        variants: payload.variants?.map((variant) => normalizeVariantMutation(variant)),
    };
};
