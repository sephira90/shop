import { describe, expect, it, vi } from "vitest";
import { effectScope, ref } from "vue";

import { useAdminCategoriesListState } from "@/composables/admin/categories/useAdminCategoriesListState";
import type { AdminCategoryRouteFilters } from "@/queries/admin/categories";
import type {
    AdminCategory,
    AdminCategoryListParams,
    CategoryStatusFilter,
} from "@/types/admin-categories";

vi.mock("@/api/admin/categories", () => ({
    listAdminCategoryOptions: vi.fn(),
    listAdminCategories: vi.fn(),
}));

import { listAdminCategories } from "@/api/admin/categories";

const listAdminCategoriesMock = listAdminCategories as unknown as ReturnType<typeof vi.fn>;

const buildCategory = (id: number): AdminCategory => ({
    id,
    parent_id: null,
    name: `Category ${id}`,
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

const createFilterState = () => {
    const searchQuery = ref("");
    const statusFilter = ref<CategoryStatusFilter>("all");

    return {
        initialPage: 1,
        searchQuery,
        statusFilter,
        buildListParams: (targetPage: number): AdminCategoryListParams => {
            const params: AdminCategoryListParams = {
                page: targetPage,
                per_page: 200,
            };
            const query = searchQuery.value.trim();

            if (query !== "") {
                params.q = query;
            }

            if (statusFilter.value !== "all") {
                params.is_active = statusFilter.value === "active";
            }

            return params;
        },
        filterSource: () =>
            [searchQuery.value, statusFilter.value] as [string, CategoryStatusFilter],
        applyParsedFilters: (parsed: AdminCategoryRouteFilters) => {
            searchQuery.value = parsed.searchQuery;
            statusFilter.value = parsed.statusFilter;

            return parsed.page;
        },
        readFiltersForPage: (targetPage: number) => ({
            searchQuery: searchQuery.value,
            statusFilter: statusFilter.value,
            page: targetPage,
        }),
    };
};

describe("useAdminCategoriesListState", () => {
    it("loads list payload with notice lifecycle", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        listAdminCategoriesMock.mockResolvedValue({
            data: [buildCategory(2)],
            meta: {
                current_page: 2,
                last_page: 5,
                per_page: 200,
                total: 999,
            },
        });

        const scope = effectScope();
        const api = scope.run(() => {
            const filterState = createFilterState();
            filterState.searchQuery.value = "  boots  ";
            filterState.statusFilter.value = "inactive";

            return useAdminCategoriesListState({
                notice: {
                    clearNotice,
                    showApiError,
                },
                filterState,
            });
        });

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadCategories(2);

        expect(listAdminCategoriesMock).toHaveBeenCalledWith({
            page: 2,
            per_page: 200,
            q: "boots",
            is_active: false,
        });
        expect(clearNotice).toHaveBeenCalledTimes(1);
        expect(showApiError).not.toHaveBeenCalled();
        expect(api.categories.value).toEqual([buildCategory(2)]);
        expect(api.page.value).toBe(2);

        scope.stop();
    });

    it("resets list state and reports notice on failure", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        listAdminCategoriesMock.mockRejectedValue(new Error("Failed to load"));

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategoriesListState({
                notice: {
                    clearNotice,
                    showApiError,
                },
                filterState: createFilterState(),
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadCategories(4);

        expect(showApiError).toHaveBeenCalledTimes(1);
        expect(api.categories.value).toEqual([]);
        expect(api.page.value).toBe(1);

        scope.stop();
    });
});
