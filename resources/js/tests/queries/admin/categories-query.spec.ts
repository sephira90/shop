import { describe, expect, it } from "vitest";

import {
    buildAdminCategoryListParams,
    buildAdminCategoryRouteQuery,
    isSameAdminCategoryRouteQuery,
    parseAdminCategoryFiltersFromRouteQuery,
} from "@/queries/admin/categories";

describe("category query", () => {
    it("builds params with search and active status", () => {
        expect(
            buildAdminCategoryListParams(2, {
                searchQuery: "  shoes  ",
                statusFilter: "active",
            }),
        ).toEqual({
            page: 2,
            per_page: 200,
            q: "shoes",
            is_active: true,
        });
    });

    it("builds params with inactive status", () => {
        expect(
            buildAdminCategoryListParams(3, {
                searchQuery: "",
                statusFilter: "inactive",
            }),
        ).toEqual({
            page: 3,
            per_page: 200,
            is_active: false,
        });
    });

    it("omits optional filters when reset", () => {
        expect(
            buildAdminCategoryListParams(1, {
                searchQuery: "   ",
                statusFilter: "all",
            }),
        ).toEqual({
            page: 1,
            per_page: 200,
        });
    });

    it("parses route query and compares normalized values", () => {
        expect(
            parseAdminCategoryFiltersFromRouteQuery({
                q: "  shoes  ",
                status: "active",
                page: "3",
            }),
        ).toEqual({
            searchQuery: "shoes",
            statusFilter: "active",
            page: 3,
        });

        expect(
            isSameAdminCategoryRouteQuery(
                { q: " shoes ", status: "active", page: "1" },
                { q: "shoes", status: "active" },
            ),
        ).toBe(true);
    });

    it("builds route query without defaults", () => {
        expect(
            buildAdminCategoryRouteQuery({
                searchQuery: "  shoes ",
                statusFilter: "inactive",
                page: 2,
            }),
        ).toEqual({
            q: "shoes",
            status: "inactive",
            page: "2",
        });
    });
});
