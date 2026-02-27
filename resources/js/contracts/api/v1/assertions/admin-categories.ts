import { ApiContractError } from "@/api/response";
import type {
    AdminCategoryParentWireDto,
    AdminCategoryWireDto,
} from "@/contracts/api/v1/admin-categories";

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === "object" && value !== null && !Array.isArray(value);

const requireString = (record: Record<string, unknown>, key: string): string => {
    const value = record[key];

    if (typeof value !== "string") {
        throw new ApiContractError(`Category payload field \`${key}\` must be string.`);
    }

    return value;
};

const requireBoolean = (record: Record<string, unknown>, key: string): boolean => {
    const value = record[key];

    if (typeof value !== "boolean") {
        throw new ApiContractError(`Category payload field \`${key}\` must be boolean.`);
    }

    return value;
};

const requireNumber = (record: Record<string, unknown>, key: string): number => {
    const value = Number(record[key]);

    if (!Number.isFinite(value)) {
        throw new ApiContractError(`Category payload field \`${key}\` must be number.`);
    }

    return value;
};

const parseNullableString = (record: Record<string, unknown>, key: string): string | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    if (typeof value === "string") {
        return value;
    }

    throw new ApiContractError(`Category payload field \`${key}\` must be string|null.`);
};

const parseNullableNumber = (record: Record<string, unknown>, key: string): number | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    const numeric = Number(value);
    if (!Number.isFinite(numeric)) {
        throw new ApiContractError(`Category payload field \`${key}\` must be number|null.`);
    }

    return numeric;
};

const parseParent = (value: unknown): AdminCategoryParentWireDto | null => {
    if (value === null) {
        return null;
    }

    if (!isRecord(value)) {
        throw new ApiContractError("Category payload field `parent` must be object|null.");
    }

    return {
        id: requireNumber(value, "id"),
        name: requireString(value, "name"),
        slug: requireString(value, "slug"),
    };
};

export const assertAdminCategoryWireDto = (value: unknown): AdminCategoryWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Category payload must be an object.");
    }

    return {
        id: requireNumber(value, "id"),
        parent_id: parseNullableNumber(value, "parent_id"),
        name: requireString(value, "name"),
        slug: requireString(value, "slug"),
        description: parseNullableString(value, "description"),
        meta_title: parseNullableString(value, "meta_title"),
        meta_description: parseNullableString(value, "meta_description"),
        is_active: requireBoolean(value, "is_active"),
        sort_order: requireNumber(value, "sort_order"),
        parent: parseParent(value.parent),
        children_count: requireNumber(value, "children_count"),
        products_count: requireNumber(value, "products_count"),
    };
};
