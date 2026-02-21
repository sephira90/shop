import type { CategoryMutationPayload } from "@/types/admin-categories";

export interface CategoryFormState {
    parent_id: string;
    name: string;
    slug: string;
    description: string;
    meta_title: string;
    meta_description: string;
    is_active: boolean;
    sort_order: string;
}

export const createCategoryFormState = (): CategoryFormState => ({
    parent_id: "",
    name: "",
    slug: "",
    description: "",
    meta_title: "",
    meta_description: "",
    is_active: true,
    sort_order: "0",
});

export const buildCategoryMutationPayload = (form: CategoryFormState): CategoryMutationPayload => {
    return {
        parent_id: form.parent_id !== "" ? Number(form.parent_id) : null,
        name: form.name.trim(),
        slug: form.slug.trim() || null,
        description: form.description.trim() || null,
        meta_title: form.meta_title.trim() || null,
        meta_description: form.meta_description.trim() || null,
        is_active: form.is_active,
        sort_order: Number(form.sort_order || "0"),
    };
};
