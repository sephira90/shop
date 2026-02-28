import { describe, expect, it, vi } from "vitest";
import { effectScope, ref } from "vue";

import { useAdminProductsListState } from "@/composables/admin/products/useAdminProductsListState";
import type { AdminProductRouteFilters } from "@/queries/admin/products";
import type { AdminProduct, AdminProductListParams } from "@/types/admin-products";

vi.mock("@/api/admin/products", () => ({
    listAdminProducts: vi.fn(),
}));

import { listAdminProducts } from "@/api/admin/products";

const listAdminProductsMock = listAdminProducts as unknown as ReturnType<typeof vi.fn>;

const buildProduct = (id: number): AdminProduct => ({
    id,
    sku: `SKU-${id}`,
    name: `Product ${id}`,
    slug: `product-${id}`,
    short_description: null,
    description: null,
    status: "active",
    is_featured: false,
    brand: null,
    weight_grams: null,
    published_at: null,
    category: null,
    variants: [],
    meta: {
        title: null,
        description: null,
    },
});

const createFilterState = () => {
    const searchQuery = ref("");

    return {
        initialPage: 1,
        searchQuery,
        buildListParams: (targetPage: number): AdminProductListParams => {
            const params: AdminProductListParams = {
                page: targetPage,
            };
            const query = searchQuery.value.trim();

            if (query !== "") {
                params.q = query;
            }

            return params;
        },
        applyParsedFilters: (parsed: AdminProductRouteFilters) => {
            searchQuery.value = parsed.searchQuery;

            return parsed.page;
        },
        readFiltersForPage: (targetPage: number) => ({
            searchQuery: searchQuery.value,
            page: targetPage,
        }),
    };
};

describe("useAdminProductsListState", () => {
    it("loads list payload with notice lifecycle", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        listAdminProductsMock.mockResolvedValue({
            data: [buildProduct(2)],
            meta: {
                current_page: 2,
                last_page: 5,
                per_page: 30,
                total: 150,
            },
        });

        const scope = effectScope();
        const api = scope.run(() => {
            const filterState = createFilterState();
            filterState.searchQuery.value = "  hoodie  ";

            return useAdminProductsListState({
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

        await api.loadProducts(2);

        expect(listAdminProductsMock).toHaveBeenCalledWith({
            page: 2,
            q: "hoodie",
        });
        expect(clearNotice).toHaveBeenCalledTimes(1);
        expect(showApiError).not.toHaveBeenCalled();
        expect(api.products.value).toEqual([buildProduct(2)]);
        expect(api.page.value).toBe(2);

        scope.stop();
    });

    it("keeps existing list state and reports notice on failure", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        listAdminProductsMock.mockRejectedValue(new Error("Failed to load"));

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProductsListState({
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

        await api.loadProducts(4);

        expect(showApiError).toHaveBeenCalledTimes(1);
        expect(api.products.value).toEqual([]);
        expect(api.page.value).toBe(1);

        scope.stop();
    });
});
