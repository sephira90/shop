import { describe, expect, it } from "vitest";

import {
    buildAdminPromotionListParams,
    buildAdminPromotionRouteQuery,
    isSameAdminPromotionRouteQuery,
    parseAdminPromotionFiltersFromRouteQuery,
} from "@/queries/admin/promotions";

describe("promotion query", () => {
    it("builds params with search and active status", () => {
        expect(
            buildAdminPromotionListParams(2, {
                searchQuery: "  vip  ",
                statusFilter: "active",
            }),
        ).toEqual({
            page: 2,
            q: "vip",
            is_active: true,
        });
    });

    it("builds params with inactive status", () => {
        expect(
            buildAdminPromotionListParams(3, {
                searchQuery: "",
                statusFilter: "inactive",
            }),
        ).toEqual({
            page: 3,
            is_active: false,
        });
    });

    it("omits optional filters when reset", () => {
        expect(
            buildAdminPromotionListParams(1, {
                searchQuery: "   ",
                statusFilter: "all",
            }),
        ).toEqual({
            page: 1,
        });
    });

    it("parses route query and compares normalized shape", () => {
        expect(
            parseAdminPromotionFiltersFromRouteQuery({
                q: "  vip  ",
                status: "active",
                page: "3",
            }),
        ).toEqual({
            searchQuery: "vip",
            statusFilter: "active",
            page: 3,
        });

        expect(
            isSameAdminPromotionRouteQuery(
                { q: " vip ", status: "active", page: "1" },
                { q: "vip", status: "active" },
            ),
        ).toBe(true);
    });

    it("builds route query without defaults", () => {
        expect(
            buildAdminPromotionRouteQuery({
                searchQuery: "  vip ",
                statusFilter: "inactive",
                page: 2,
            }),
        ).toEqual({
            q: "vip",
            status: "inactive",
            page: "2",
        });
    });
});
