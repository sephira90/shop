import { describe, expect, it } from "vitest";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import { useAdminPromotionsFilterState } from "@/composables/admin/promotions/useAdminPromotionsFilterState";

const createRouteSync = (query: Record<string, unknown>): AdminRouteSyncOptions => ({
    route: {
        query,
    },
    router: {
        replace: () => {},
    },
});

describe("useAdminPromotionsFilterState", () => {
    it("provides default filter state and builds list params", () => {
        const state = useAdminPromotionsFilterState();

        expect(state.initialPage).toBe(1);
        expect(state.filterSource()).toEqual(["", "all"]);

        state.searchQuery.value = "  vip  ";
        state.statusFilter.value = "active";

        expect(state.buildListParams(3)).toEqual({
            page: 3,
            q: "vip",
            is_active: true,
        });
    });

    it("hydrates from route query and supports apply/read helpers", () => {
        const state = useAdminPromotionsFilterState(
            createRouteSync({
                q: "  winter ",
                status: "inactive",
                page: "4",
            }),
        );

        expect(state.initialPage).toBe(4);
        expect(state.searchQuery.value).toBe("winter");
        expect(state.statusFilter.value).toBe("inactive");

        const parsedPage = state.applyParsedFilters({
            searchQuery: "flash",
            statusFilter: "active",
            page: 6,
        });

        expect(parsedPage).toBe(6);
        expect(state.searchQuery.value).toBe("flash");
        expect(state.statusFilter.value).toBe("active");
        expect(state.readFiltersForPage(8)).toEqual({
            searchQuery: "flash",
            statusFilter: "active",
            page: 8,
        });
    });
});
