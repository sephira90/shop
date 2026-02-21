import { describe, expect, it } from "vitest";

import { buildAdminPromotionListParams } from "@/queries/admin/promotions";

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
});
