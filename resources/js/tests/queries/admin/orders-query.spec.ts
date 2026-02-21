import { describe, expect, it } from "vitest";

import { buildAdminOrderListParams } from "@/queries/admin/orders";

describe("order query", () => {
    it("builds list params from filter state", () => {
        expect(
            buildAdminOrderListParams(3, {
                search: " mary@example.com ",
                orderStatus: "completed",
                paymentStatus: "captured",
                shipmentStatus: "delivered",
            }),
        ).toEqual({
            page: 3,
            q: "mary@example.com",
            status: "completed",
            payment_status: "captured",
            shipment_status: "delivered",
        });
    });

    it("omits optional params when all filters are disabled", () => {
        expect(
            buildAdminOrderListParams(1, {
                search: "   ",
                orderStatus: "all",
                paymentStatus: "all",
                shipmentStatus: "all",
            }),
        ).toEqual({
            page: 1,
        });
    });

    it("includes only active filters", () => {
        expect(
            buildAdminOrderListParams(2, {
                search: "",
                orderStatus: "all",
                paymentStatus: "failed",
                shipmentStatus: "all",
            }),
        ).toEqual({
            page: 2,
            payment_status: "failed",
        });
    });
});
