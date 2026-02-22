import { describe, expect, it } from "vitest";

import {
    formatMoney,
    formatOrderAddress,
    formatOrderDate,
    orderStatusClass,
    paymentStatusClass,
    shipmentStatusClass,
} from "@/utils/order-presentation";

describe("order presentation utils", () => {
    it("formats money with currency", () => {
        expect(formatMoney(1234.5, "USD")).toBe("$1,234.50");
    });

    it("formats order address with fallback", () => {
        expect(
            formatOrderAddress({
                line1: "Main st 1",
                city: "New York",
                country: "US",
                postcode: "10001",
            }),
        ).toBe("Main st 1, New York, US, 10001");
        expect(formatOrderAddress(null)).toBe("Not provided");
    });

    it("formats order date with fallback", () => {
        expect(formatOrderDate(null)).toBe("Unknown date");
        expect(formatOrderDate("invalid-date", "N/A")).toBe("N/A");
    });

    it("maps order statuses to status chips", () => {
        expect(orderStatusClass("paid")).toBe("status-chip--good");
        expect(paymentStatusClass("failed")).toBe("status-chip--bad");
        expect(shipmentStatusClass("packed")).toBe("status-chip--info");
        expect(orderStatusClass("random")).toBe("status-chip--neutral");
    });
});
