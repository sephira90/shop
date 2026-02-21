import type {
    AdminProduct,
    AdminProductCategory,
    ProductMutationPayload,
    ProductStatus,
    ProductVariant,
    ProductVariantInventory,
} from "@/types/admin-products";

import {
    asArray,
    asRecord,
    toBoolean,
    toInteger,
    toNullableInteger,
    toNullableString,
    toNumber,
    toString,
} from "@/mappers/common";

const mapProductStatus = (value: unknown): ProductStatus => {
    const normalized = toString(value).toLowerCase();

    if (normalized === "active" || normalized === "archived") {
        return normalized;
    }

    return "draft";
};

const mapProductCategory = (value: unknown): AdminProductCategory | null => {
    const record = asRecord(value);
    const id = toInteger(record.id);

    if (id <= 0) {
        return null;
    }

    return {
        id,
        name: toString(record.name),
        slug: toString(record.slug),
    };
};

const mapVariantInventory = (value: unknown): ProductVariantInventory | null => {
    const record = asRecord(value);

    if (Object.keys(record).length === 0) {
        return null;
    }

    return {
        quantity: toNullableInteger(record.quantity),
        reserved_quantity: toNullableInteger(record.reserved_quantity),
        low_stock_threshold: toNullableInteger(record.low_stock_threshold),
    };
};

const mapVariantAttributes = (value: unknown): Record<string, unknown> | null => {
    const record = asRecord(value);

    if (Object.keys(record).length === 0) {
        return null;
    }

    return record;
};

const mapProductVariant = (value: unknown): ProductVariant => {
    const record = asRecord(value);

    return {
        id: toInteger(record.id),
        sku: toString(record.sku),
        name: toString(record.name),
        attributes: mapVariantAttributes(record.attributes),
        price: toNumber(record.price),
        compare_at_price:
            record.compare_at_price === null || record.compare_at_price === undefined
                ? null
                : toNumber(record.compare_at_price),
        currency: toString(record.currency, "USD"),
        is_active: toBoolean(record.is_active, true),
        inventory: mapVariantInventory(record.inventory),
    };
};

export const mapAdminProductFromApi = (value: unknown): AdminProduct => {
    const record = asRecord(value);
    const meta = asRecord(record.meta);

    return {
        id: toInteger(record.id),
        sku: toString(record.sku),
        name: toString(record.name),
        slug: toString(record.slug),
        short_description: toNullableString(record.short_description),
        description: toNullableString(record.description),
        status: mapProductStatus(record.status),
        is_featured: toBoolean(record.is_featured),
        brand: toNullableString(record.brand),
        weight_grams: toNullableInteger(record.weight_grams),
        category: mapProductCategory(record.category),
        meta: {
            title: toNullableString(meta.title),
            description: toNullableString(meta.description),
        },
        variants: asArray(record.variants).map((variant) => mapProductVariant(variant)),
        published_at: toNullableString(record.published_at),
    };
};

export const mapAdminProductListFromApi = (value: unknown): AdminProduct[] => {
    return asArray(value).map((item) => mapAdminProductFromApi(item));
};

export const toProductMutationDto = (payload: ProductMutationPayload): ProductMutationPayload => {
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
        variants: payload.variants,
    };
};
