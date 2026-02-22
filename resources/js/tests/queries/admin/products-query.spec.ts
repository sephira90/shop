import { describe, expect, it } from "vitest";

import {
    buildAdminProductListParams,
    buildAdminProductRouteQuery,
    isSameAdminProductRouteQuery,
    parseAdminProductFiltersFromRouteQuery,
} from "@/queries/admin/products";

describe("product query", () => {
    it("builds list params from search state", () => {
        expect(
            buildAdminProductListParams(4, {
                searchQuery: "  SKU-001  ",
            }),
        ).toEqual({
            page: 4,
            q: "SKU-001",
        });
    });

    it("omits query param when search is empty", () => {
        expect(
            buildAdminProductListParams(1, {
                searchQuery: "   ",
            }),
        ).toEqual({
            page: 1,
        });
    });

    it("parses and builds route query", () => {
        expect(
            parseAdminProductFiltersFromRouteQuery({
                q: "  SKU-001  ",
                page: "4",
            }),
        ).toEqual({
            searchQuery: "SKU-001",
            page: 4,
        });

        expect(
            buildAdminProductRouteQuery({
                searchQuery: " SKU-001 ",
                page: 4,
            }),
        ).toEqual({
            q: "SKU-001",
            page: "4",
        });
    });

    it("compares route queries by normalized values", () => {
        expect(isSameAdminProductRouteQuery({ q: " test ", page: "1" }, { q: "test" })).toBe(true);
    });
});
