import { describe, expect, it } from "vitest";

import { formatPrice } from "@/utils/format";

describe("format utils", () => {
    it("formats prices with default usd fallback", () => {
        expect(formatPrice(1234.5)).toBe("$1,234.50");
        expect(formatPrice(undefined)).toBe("$0.00");
    });

    it("formats prices with provided currency", () => {
        expect(formatPrice(99.9, "eur")).toBe("€99.90");
    });
});
