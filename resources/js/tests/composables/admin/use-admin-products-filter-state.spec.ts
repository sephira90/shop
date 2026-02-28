import { describe, expect, it } from "vitest";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import { useAdminProductsFilterState } from "@/composables/admin/products/useAdminProductsFilterState";

const createRouteSync = (query: Record<string, unknown>): AdminRouteSyncOptions => ({
    route: {
        query,
    },
    router: {
        replace: () => {},
    },
});

describe("useAdminProductsFilterState", () => {
    it("provides default filter state and builds list params", () => {
        const state = useAdminProductsFilterState();

        expect(state.initialPage).toBe(1);

        state.searchQuery.value = "  jacket  ";

        expect(state.buildListParams(3)).toEqual({
            page: 3,
            q: "jacket",
        });
    });

    it("hydrates from route query and supports apply/read helpers", () => {
        const state = useAdminProductsFilterState(
            createRouteSync({
                q: "  boots ",
                page: "4",
            }),
        );

        expect(state.initialPage).toBe(4);
        expect(state.searchQuery.value).toBe("boots");

        const parsedPage = state.applyParsedFilters({
            searchQuery: "hoodie",
            page: 6,
        });

        expect(parsedPage).toBe(6);
        expect(state.searchQuery.value).toBe("hoodie");
        expect(state.readFiltersForPage(8)).toEqual({
            searchQuery: "hoodie",
            page: 8,
        });
    });
});
