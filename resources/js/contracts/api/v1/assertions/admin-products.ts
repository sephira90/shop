import { ApiContractError } from "@/api/response";
import { createFieldParsers, isRecord } from "@/contracts/api/v1/assertions/primitives";
import type {
    AdminProductCategoryWireDto,
    AdminProductMetaWireDto,
    AdminProductStatusWireDto,
    AdminProductVariantInventoryWireDto,
    AdminProductVariantWireDto,
    AdminProductWireDto,
} from "@/contracts/api/v1/admin-products";

const { parseNullableNumber, parseNullableString, requireBoolean, requireNumber, requireString } =
    createFieldParsers("Product");

const parseStatus = (record: Record<string, unknown>, key: string): AdminProductStatusWireDto => {
    const value = requireString(record, key);

    if (value === "draft" || value === "active" || value === "archived") {
        return value;
    }

    throw new ApiContractError(
        `Product payload field \`${key}\` must be 'draft'|'active'|'archived'.`,
    );
};

const parseCategory = (value: unknown): AdminProductCategoryWireDto | null => {
    if (value === null) {
        return null;
    }

    if (!isRecord(value)) {
        throw new ApiContractError("Product payload field `category` must be object|null.");
    }

    return {
        id: requireNumber(value, "id"),
        name: requireString(value, "name"),
        slug: requireString(value, "slug"),
    };
};

const parseMeta = (value: unknown): AdminProductMetaWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Product payload field `meta` must be object.");
    }

    return {
        title: parseNullableString(value, "title"),
        description: parseNullableString(value, "description"),
    };
};

const parseInventory = (value: unknown): AdminProductVariantInventoryWireDto | null => {
    if (value === null) {
        return null;
    }

    if (!isRecord(value)) {
        throw new ApiContractError("Product payload field `inventory` must be object|null.");
    }

    return {
        quantity: parseNullableNumber(value, "quantity"),
        reserved_quantity: parseNullableNumber(value, "reserved_quantity"),
        available_quantity: parseNullableNumber(value, "available_quantity"),
    };
};

const parseAttributes = (value: unknown): Record<string, unknown> | null => {
    if (value === null) {
        return null;
    }

    if (!isRecord(value)) {
        throw new ApiContractError("Product variant field `attributes` must be object|null.");
    }

    return value;
};

const parseVariants = (value: unknown): AdminProductVariantWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Product payload field `variants` must be array.");
    }

    return value.map((item): AdminProductVariantWireDto => {
        if (!isRecord(item)) {
            throw new ApiContractError("Product variant payload must be object.");
        }

        return {
            id: requireNumber(item, "id"),
            sku: requireString(item, "sku"),
            name: requireString(item, "name"),
            attributes: parseAttributes(item.attributes),
            price: requireNumber(item, "price"),
            compare_at_price: parseNullableNumber(item, "compare_at_price"),
            currency: requireString(item, "currency"),
            is_active: requireBoolean(item, "is_active"),
            inventory: parseInventory(item.inventory),
        };
    });
};

export const assertAdminProductWireDto = (value: unknown): AdminProductWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Product payload must be an object.");
    }

    return {
        id: requireNumber(value, "id"),
        sku: requireString(value, "sku"),
        name: requireString(value, "name"),
        slug: requireString(value, "slug"),
        short_description: parseNullableString(value, "short_description"),
        description: parseNullableString(value, "description"),
        status: parseStatus(value, "status"),
        is_featured: requireBoolean(value, "is_featured"),
        brand: parseNullableString(value, "brand"),
        weight_grams: parseNullableNumber(value, "weight_grams"),
        category: parseCategory(value.category),
        meta: parseMeta(value.meta),
        variants: parseVariants(value.variants),
        published_at: parseNullableString(value, "published_at"),
        created_at: parseNullableString(value, "created_at"),
        updated_at: parseNullableString(value, "updated_at"),
    };
};
