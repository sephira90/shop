import { ApiContractError } from "@/api/response";
import type {
    CatalogProductVariantWireDto,
    CatalogProductWireDto,
} from "@/contracts/api/v1/catalog";

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === "object" && value !== null && !Array.isArray(value);

const requireString = (record: Record<string, unknown>, key: string): string => {
    const value = record[key];

    if (typeof value !== "string") {
        throw new ApiContractError(`Catalog payload field \`${key}\` must be string.`);
    }

    return value;
};

const requireBoolean = (record: Record<string, unknown>, key: string): boolean => {
    const value = record[key];

    if (typeof value !== "boolean") {
        throw new ApiContractError(`Catalog payload field \`${key}\` must be boolean.`);
    }

    return value;
};

const requireNumber = (record: Record<string, unknown>, key: string): number => {
    const value = Number(record[key]);

    if (!Number.isFinite(value)) {
        throw new ApiContractError(`Catalog payload field \`${key}\` must be number.`);
    }

    return value;
};

const parseNullableString = (record: Record<string, unknown>, key: string): string | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    if (typeof value !== "string") {
        throw new ApiContractError(`Catalog payload field \`${key}\` must be string|null.`);
    }

    return value;
};

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
