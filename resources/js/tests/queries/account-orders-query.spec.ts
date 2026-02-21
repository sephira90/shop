import { describe, expect, it } from "vitest";

import {
    buildAccountOrdersListParams,
    buildAccountOrdersRouteQuery,
    isSameAccountOrdersRouteQuery,
    parseAccountOrdersFiltersFromRouteQuery,
} from "@/queries/account-orders";

describe("account orders query", () => {
    it("parses route query with normalized values", () => {
        expect(
            parseAccountOrdersFiltersFromRouteQuery({
                q: "  ORD-100  ",
                status: "completed",
                page: "3",
            }),
        ).toEqual({
            searchQuery: "ORD-100",
            statusFilter: "completed",
            page: 3,
        });
    });

    it("falls back to defaults for invalid values", () => {
        expect(
            parseAccountOrdersFiltersFromRouteQuery({
                q: "",
                status: "random",
                page: "0",
            }),
        ).toEqual({
            searchQuery: "",
            statusFilter: "all",
            page: 1,
        });
    });

    it("builds route query without defaults", () => {
        expect(
            buildAccountOrdersRouteQuery({
                searchQuery: "  jane@example.com  ",
                statusFilter: "paid",
                page: 2,
            }),
        ).toEqual({
            q: "jane@example.com",
            status: "paid",
            page: "2",
        });
    });

    it("builds list params from page and filters", () => {
        expect(
            buildAccountOrdersListParams(4, {
                searchQuery: "  jane@example.com ",
                statusFilter: "paid",
            }),
        ).toEqual({
            page: 4,
            q: "jane@example.com",
            status: "paid",
        });

        expect(
            buildAccountOrdersListParams(1, {
                searchQuery: "   ",
                statusFilter: "all",
            }),
        ).toEqual({
            page: 1,
        });
    });

    it("compares route queries by normalized filters", () => {
        expect(
            isSameAccountOrdersRouteQuery(
                { q: "  test ", status: "paid", page: "1" },
                { q: "test", status: "paid" },
            ),
        ).toBe(true);

        expect(
            isSameAccountOrdersRouteQuery(
                { q: "test", status: "paid", page: "2" },
                { q: "test", status: "paid", page: "3" },
            ),
        ).toBe(false);
    });
});
