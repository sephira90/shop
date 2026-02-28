import { describe, expect, it, vi } from "vitest";
import { effectScope } from "vue";

import { useAdminProductCategoriesState } from "@/composables/admin/products/useAdminProductCategoriesState";
import type { AdminCategory } from "@/types/admin-categories";

vi.mock("@/api/admin/categories", () => ({
    listAdminCategories: vi.fn(),
}));

import { listAdminCategories } from "@/api/admin/categories";

const listAdminCategoriesMock = listAdminCategories as unknown as ReturnType<typeof vi.fn>;

const buildCategory = (id: number, name: string): AdminCategory => ({
    id,
    parent_id: null,
    name,
    slug: `category-${id}`,
    description: null,
    meta_title: null,
    meta_description: null,
    is_active: true,
    sort_order: id,
    parent: null,
    children_count: 0,
    products_count: 0,
});

describe("useAdminProductCategoriesState", () => {
    it("loads and sorts category options across all pages", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        listAdminCategoriesMock
            .mockResolvedValueOnce({
                data: [buildCategory(2, "Winter"), buildCategory(1, "Autumn")],
                meta: {
                    current_page: 1,
                    last_page: 2,
                    per_page: 200,
                    total: 3,
                },
            })
            .mockResolvedValueOnce({
                data: [buildCategory(3, "Basics")],
                meta: {
                    current_page: 2,
                    last_page: 2,
                    per_page: 200,
                    total: 3,
                },
            });

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProductCategoriesState({
                clearNotice,
                showApiError,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadCategories();

        expect(listAdminCategoriesMock).toHaveBeenNthCalledWith(1, {
            page: 1,
            per_page: 200,
        });
        expect(listAdminCategoriesMock).toHaveBeenNthCalledWith(2, {
            page: 2,
            per_page: 200,
        });
        expect(clearNotice).toHaveBeenCalledTimes(1);
        expect(showApiError).not.toHaveBeenCalled();
        expect(api.categories.value).toEqual([
            {
                id: 1,
                name: "Autumn",
                slug: "category-1",
            },
            {
                id: 3,
                name: "Basics",
                slug: "category-3",
            },
            {
                id: 2,
                name: "Winter",
                slug: "category-2",
            },
        ]);
        expect(api.isLoadingCategories.value).toBe(false);

        scope.stop();
    });

    it("clears options and reports notice on failure", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        listAdminCategoriesMock.mockRejectedValue(new Error("Failed to load"));

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProductCategoriesState({
                clearNotice,
                showApiError,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadCategories();

        expect(clearNotice).toHaveBeenCalledTimes(1);
        expect(showApiError).toHaveBeenCalledTimes(1);
        expect(api.categories.value).toEqual([]);
        expect(api.isLoadingCategories.value).toBe(false);

        scope.stop();
    });
});
