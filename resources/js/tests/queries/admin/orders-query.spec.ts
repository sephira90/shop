import { describe, expect, it } from "vitest";

import {
    buildAdminOrderListParams,
    buildAdminOrderRouteQuery,
    isSameAdminOrderRouteQuery,
    parseAdminOrderFiltersFromRouteQuery,
} from "@/queries/admin/orders";

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

    it("parses route query with normalized values", () => {
        expect(
            parseAdminOrderFiltersFromRouteQuery({
                q: "  ORD-22  ",
                status: "completed",
                payment_status: "captured",
                shipment_status: "delivered",
                page: "3",
            }),
        ).toEqual({
            search: "ORD-22",
            orderStatus: "completed",
            paymentStatus: "captured",
            shipmentStatus: "delivered",
            page: 3,
        });
    });

    it("builds route query without default filters", () => {
        expect(
            buildAdminOrderRouteQuery({
                search: "  mary@example.com ",
                orderStatus: "all",
                paymentStatus: "captured",
                shipmentStatus: "all",
                page: 2,
            }),
        ).toEqual({
            q: "mary@example.com",
            payment_status: "captured",
            page: "2",
        });
    });

    it("compares route queries by normalized filters", () => {
        expect(
            isSameAdminOrderRouteQuery(
                {
                    q: "  ORD-22 ",
                    status: "completed",
                    payment_status: "captured",
                },
                {
                    q: "ORD-22",
                    status: "completed",
                    payment_status: "captured",
                    page: "1",
                },
            ),
        ).toBe(true);
    });
});
