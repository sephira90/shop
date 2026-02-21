import type { CategoryMutationPayload, AdminCategory } from '@/types/admin-categories';

import { asArray, asRecord, toBoolean, toInteger, toNullableInteger, toNullableString, toString } from '@/mappers/common';

const mapCategoryParent = (value: unknown): AdminCategory['parent'] => {
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

export const mapAdminCategoryFromApi = (value: unknown): AdminCategory => {
    const record = asRecord(value);

    return {
        id: toInteger(record.id),
        parent_id: toNullableInteger(record.parent_id),
        name: toString(record.name),
        slug: toString(record.slug),
        description: toNullableString(record.description),
        meta_title: toNullableString(record.meta_title),
        meta_description: toNullableString(record.meta_description),
        is_active: toBoolean(record.is_active, true),
        sort_order: toInteger(record.sort_order),
        parent: mapCategoryParent(record.parent),
        children_count: toInteger(record.children_count),
        products_count: toInteger(record.products_count),
    };
};

export const mapAdminCategoryListFromApi = (value: unknown): AdminCategory[] => {
    return asArray(value).map((item) => mapAdminCategoryFromApi(item));
};

export const toCategoryMutationDto = (payload: CategoryMutationPayload): CategoryMutationPayload => {
    return {
        parent_id: payload.parent_id,
        name: payload.name.trim(),
        slug: payload.slug,
        description: payload.description,
        meta_title: payload.meta_title,
        meta_description: payload.meta_description,
        is_active: payload.is_active,
        sort_order: payload.sort_order,
    };
};
