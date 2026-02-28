import { describe, expect, it } from "vitest";

import { normalizeEnumQuery, toSingleQueryValue } from "@/queries/route-query";
import {
    buildAdminRouteQuery,
    isSameAdminRouteQuery,
    parseAdminRouteFilters,
    type AdminRouteQuerySchema,
} from "@/queries/admin/route-query-schema";

interface TestFilters {
    searchQuery: string;
    statusFilter: "all" | "active" | "inactive";
}

const TEST_DEFAULT_FILTERS: TestFilters = {
    searchQuery: "",
    statusFilter: "all",
};

const TEST_SCHEMA: AdminRouteQuerySchema<TestFilters> = {
    fields: [
        {
            key: "searchQuery",
            queryKey: "q",
            parse: (value) => toSingleQueryValue(value).trim(),
            format: (value) => {
                const query = String(value).trim();

                return query === "" ? null : query;
            },
        },
        {
            key: "statusFilter",
            queryKey: "status",
            parse: (value) =>
                normalizeEnumQuery(value, ["all", "active", "inactive"] as const, "all"),
            format: (value) => (value === "all" ? null : value),
        },
    ],
};

describe("admin route query schema", () => {
    it("parses route query with defaults and normalized page", () => {
        expect(
            parseAdminRouteFilters(
                {
                    q: "  summer  ",
                    status: "active",
                    page: "3",
                },
                TEST_SCHEMA,
                TEST_DEFAULT_FILTERS,
            ),
        ).toEqual({
            searchQuery: "summer",
            statusFilter: "active",
            page: 3,
        });

        expect(
            parseAdminRouteFilters(
                {
                    page: "invalid",
                },
                TEST_SCHEMA,
                TEST_DEFAULT_FILTERS,
            ),
        ).toEqual({
            searchQuery: "",
            statusFilter: "all",
            page: 1,
        });
    });

    it("builds route query without default values", () => {
        expect(
            buildAdminRouteQuery(
                {
                    searchQuery: "  spring ",
                    statusFilter: "inactive",
                    page: 2,
                },
                TEST_SCHEMA,
            ),
        ).toEqual({
            q: "spring",
            status: "inactive",
            page: "2",
        });

        expect(
            buildAdminRouteQuery(
                {
                    searchQuery: "   ",
                    statusFilter: "all",
                    page: 1,
                },
                TEST_SCHEMA,
            ),
        ).toEqual({});
    });

    it("compares route queries by normalized schema fields", () => {
        expect(
            isSameAdminRouteQuery(
                {
                    q: " spring ",
                    status: "active",
                    page: "1",
                },
                {
                    q: "spring",
                    status: "active",
                },
                TEST_SCHEMA,
                TEST_DEFAULT_FILTERS,
            ),
        ).toBe(true);
    });
});
