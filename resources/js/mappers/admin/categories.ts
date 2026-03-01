import type {
    AdminCategoryOptionWireDto,
    AdminCategoryWireDto,
} from "@/contracts/api/v1/admin-categories";
import type {
    CategoryMutationPayload,
    AdminCategory,
    AdminCategoryOption,
} from "@/types/admin-categories";

const mapCategoryParent = (value: AdminCategoryWireDto["parent"]): AdminCategory["parent"] => {
    if (value === null) {
        return null;
    }

    return {
        id: value.id,
        name: value.name,
        slug: value.slug,
    };
};

export const mapAdminCategoryFromApi = (value: AdminCategoryWireDto): AdminCategory => {
    return {
        id: value.id,
        parent_id: value.parent_id,
        name: value.name,
        slug: value.slug,
        description: value.description,
        meta_title: value.meta_title,
        meta_description: value.meta_description,
        is_active: value.is_active,
        sort_order: value.sort_order,
        parent: mapCategoryParent(value.parent),
        children_count: value.children_count,
        products_count: value.products_count,
    };
};

export const mapAdminCategoryListFromApi = (value: AdminCategoryWireDto[]): AdminCategory[] => {
    return value.map((item) => mapAdminCategoryFromApi(item));
};

export const mapAdminCategoryOptionFromApi = (
    value: AdminCategoryOptionWireDto,
): AdminCategoryOption => {
    return {
        id: value.id,
        parent_id: value.parent_id,
        name: value.name,
        slug: value.slug,
        is_active: value.is_active,
    };
};

export const mapAdminCategoryOptionListFromApi = (
    value: AdminCategoryOptionWireDto[],
): AdminCategoryOption[] => {
    return value.map((item) => mapAdminCategoryOptionFromApi(item));
};

export const toCategoryMutationDto = (
    payload: CategoryMutationPayload,
): CategoryMutationPayload => {
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
