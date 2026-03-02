import { ApiContractError } from "@/api/response";
import { createFieldParsers, isRecord } from "@/contracts/api/v1/assertions/primitives";
import type {
    CatalogProductVariantWireDto,
    CatalogProductWireDto,
} from "@/contracts/api/v1/catalog";

const { parseNullableString, requireBoolean, requireNumber, requireString } =
    createFieldParsers("Catalog");

export const assertCatalogProductVariantWireDto = (
    value: unknown,
): CatalogProductVariantWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Catalog variant payload must be an object.");
    }

    return {
        id: requireNumber(value, "id"),
        sku: requireString(value, "sku"),
        name: requireString(value, "name"),
        price: requireNumber(value, "price"),
        currency: requireString(value, "currency"),
        is_active: requireBoolean(value, "is_active"),
    };
};

const parseVariants = (value: unknown): CatalogProductVariantWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Catalog payload field `variants` must be array.");
    }

    return value.map((item) => assertCatalogProductVariantWireDto(item));
};

export const assertCatalogProductWireDto = (value: unknown): CatalogProductWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Catalog payload must be an object.");
    }

    return {
        id: requireNumber(value, "id"),
        name: requireString(value, "name"),
        slug: requireString(value, "slug"),
        short_description: parseNullableString(value, "short_description"),
        description: parseNullableString(value, "description"),
        variants: parseVariants(value.variants),
    };
};

export const assertCatalogProductWireDtoList = (value: unknown): CatalogProductWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Catalog list payload must be array.");
    }

    return value.map((item) => assertCatalogProductWireDto(item));
};
