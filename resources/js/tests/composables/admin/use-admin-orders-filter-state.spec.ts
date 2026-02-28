import { describe, expect, it } from "vitest";

import { useAdminOrdersFilterState } from "@/composables/admin/orders/useAdminOrdersFilterState";
import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";

const createRouteSync = (query: Record<string, unknown>): AdminRouteSyncOptions => ({
    route: {
        query,
    },
    router: {
        replace: () => {},
    },
});

describe("useAdminOrdersFilterState", () => {
    it("provides default filters and builds list params", () => {
        const state = useAdminOrdersFilterState();

        expect(state.initialPage).toBe(1);
        expect(state.filterSource()).toEqual(["", "all", "all", "all"]);

        state.filters.search = "  john@example.com  ";
        state.filters.orderStatus = "completed";
        state.filters.paymentStatus = "captured";
        state.filters.shipmentStatus = "delivered";

        expect(state.buildListParams(2)).toEqual({
            page: 2,
            q: "john@example.com",
            status: "completed",
            payment_status: "captured",
            shipment_status: "delivered",
        });
    });

    it("hydrates from route query and supports apply/read helpers", () => {
        const state = useAdminOrdersFilterState(
            createRouteSync({
                q: "  vip  ",
                status: "processing",
                payment_status: "authorized",
                shipment_status: "shipped",
                page: "3",
            }),
        );

        expect(state.initialPage).toBe(3);
        expect(state.filters.search).toBe("vip");
        expect(state.filters.orderStatus).toBe("processing");
        expect(state.filters.paymentStatus).toBe("authorized");
        expect(state.filters.shipmentStatus).toBe("shipped");

        const parsedPage = state.applyParsedFilters({
            search: "buyer@example.com",
            orderStatus: "completed",
            paymentStatus: "captured",
            shipmentStatus: "delivered",
            page: 5,
        });

        expect(parsedPage).toBe(5);
        expect(state.filters.search).toBe("buyer@example.com");
        expect(state.filters.orderStatus).toBe("completed");
        expect(state.filters.paymentStatus).toBe("captured");
        expect(state.filters.shipmentStatus).toBe("delivered");
        expect(state.readFiltersForPage(7)).toEqual({
            search: "buyer@example.com",
            orderStatus: "completed",
            paymentStatus: "captured",
            shipmentStatus: "delivered",
            page: 7,
        });
    });
});
