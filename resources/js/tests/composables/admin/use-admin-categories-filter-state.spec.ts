import { describe, expect, it } from "vitest";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import { useAdminCategoriesFilterState } from "@/composables/admin/categories/useAdminCategoriesFilterState";

const createRouteSync = (query: Record<string, unknown>): AdminRouteSyncOptions => ({
    route: {
        query,
    },
    router: {
        replace: () => {},
    },
});

describe("useAdminCategoriesFilterState", () => {
    it("provides default filter state and builds list params", () => {
        const state = useAdminCategoriesFilterState();

        expect(state.initialPage).toBe(1);
        expect(state.filterSource()).toEqual(["", "all"]);

        state.searchQuery.value = "  shoes  ";
        state.statusFilter.value = "inactive";

        expect(state.buildListParams(2)).toEqual({
            page: 2,
            per_page: 200,
            q: "shoes",
            is_active: false,
        });
    });

    it("hydrates from route query and supports apply/read helpers", () => {
        const state = useAdminCategoriesFilterState(
            createRouteSync({
                q: "  accessories ",
                status: "active",
                page: "5",
            }),
        );

        expect(state.initialPage).toBe(5);
        expect(state.searchQuery.value).toBe("accessories");
        expect(state.statusFilter.value).toBe("active");

        const parsedPage = state.applyParsedFilters({
            searchQuery: "boots",
            statusFilter: "inactive",
            page: 7,
        });

        expect(parsedPage).toBe(7);
        expect(state.searchQuery.value).toBe("boots");
        expect(state.statusFilter.value).toBe("inactive");
        expect(state.readFiltersForPage(9)).toEqual({
            searchQuery: "boots",
            statusFilter: "inactive",
            page: 9,
        });
    });
});
