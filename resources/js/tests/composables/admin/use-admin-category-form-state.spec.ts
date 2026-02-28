import { describe, expect, it, vi } from "vitest";
import { effectScope } from "vue";

import { useAdminCategoryFormState } from "@/composables/admin/categories/useAdminCategoryFormState";
import type { AdminCategory } from "@/types/admin-categories";

const buildCategory = (id: number): AdminCategory => ({
    id,
    parent_id: 3,
    name: `Category ${id}`,
    slug: `category-${id}`,
    description: "Desc",
    meta_title: "Meta title",
    meta_description: "Meta description",
    is_active: false,
    sort_order: 14,
    parent: {
        id: 3,
        name: "Parent",
        slug: "parent",
    },
    children_count: 2,
    products_count: 5,
});

describe("useAdminCategoryFormState", () => {
    it("hydrates form for edit and invokes ui notice/effect adapters", () => {
        const clearNotice = vi.fn();
        const scrollToTop = vi.fn();

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategoryFormState({
                clearNotice,
                scrollToTop,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        const category = buildCategory(9);
        api.startEdit(category);

        expect(api.editingId.value).toBe(9);
        expect(api.form.parent_id).toBe("3");
        expect(api.form.name).toBe("Category 9");
        expect(api.form.slug).toBe("category-9");
        expect(api.form.description).toBe("Desc");
        expect(api.form.meta_title).toBe("Meta title");
        expect(api.form.meta_description).toBe("Meta description");
        expect(api.form.is_active).toBe(false);
        expect(api.form.sort_order).toBe("14");
        expect(clearNotice).toHaveBeenCalledTimes(1);
        expect(scrollToTop).toHaveBeenCalledTimes(1);

        scope.stop();
    });

    it("resets form state to defaults", () => {
        const clearNotice = vi.fn();

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategoryFormState({
                clearNotice,
                scrollToTop: vi.fn(),
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.startEdit(buildCategory(7));
        api.resetForm();

        expect(api.editingId.value).toBeNull();
        expect(api.form).toEqual({
            parent_id: "",
            name: "",
            slug: "",
            description: "",
            meta_title: "",
            meta_description: "",
            is_active: true,
            sort_order: "0",
        });
        expect(clearNotice).toHaveBeenCalledTimes(2);

        scope.stop();
    });
});
