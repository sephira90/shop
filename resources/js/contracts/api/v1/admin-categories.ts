export interface AdminCategoryParentWireDto {
    id: number;
    name: string;
    slug: string;
}

export interface AdminCategoryWireDto {
    id: number;
    parent_id: number | null;
    name: string;
    slug: string;
    description: string | null;
    meta_title: string | null;
    meta_description: string | null;
    is_active: boolean;
    sort_order: number;
    parent: AdminCategoryParentWireDto | null;
    children_count: number;
    products_count: number;
}

export interface AdminCategoryOptionWireDto {
    id: number;
    parent_id: number | null;
    name: string;
    slug: string;
    is_active: boolean;
}
