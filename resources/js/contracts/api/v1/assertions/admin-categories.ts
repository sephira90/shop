import { ApiContractError } from "@/api/response";
import { createFieldParsers, isRecord } from "@/contracts/api/v1/assertions/primitives";
import type {
    AdminCategoryOptionWireDto,
    AdminCategoryParentWireDto,
    AdminCategoryWireDto,
} from "@/contracts/api/v1/admin-categories";

const { parseNullableNumber, parseNullableString, requireBoolean, requireNumber, requireString } =
    createFieldParsers("Category");

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

export const assertAdminCategoryOptionWireDto = (value: unknown): AdminCategoryOptionWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Category option payload must be an object.");
    }

    return {
        id: requireNumber(value, "id"),
        parent_id: parseNullableNumber(value, "parent_id"),
        name: requireString(value, "name"),
        slug: requireString(value, "slug"),
        is_active: requireBoolean(value, "is_active"),
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
