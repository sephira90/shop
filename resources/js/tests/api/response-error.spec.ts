import { describe, expect, it } from "vitest";

import { parseApiErrorMessage } from "@/api/response";

describe("api response error parser", () => {
    it("returns first validation error from unified error envelope", () => {
        expect(
            parseApiErrorMessage(
                {
                    error: {
                        message: "Validation failed.",
                        validation: {
                            email: ["The email field is required."],
                        },
                    },
                },
                "Fallback error",
            ),
        ).toBe("The email field is required.");
    });

    it("appends request id for non-validation unified errors", () => {
        expect(
            parseApiErrorMessage(
                {
                    error: {
                        message: "Checkout failed.",
                        request_id: "req-123",
                    },
                },
                "Fallback error",
            ),
        ).toBe("Checkout failed. (request: req-123)");
    });

    it("supports legacy top-level validation shape as fallback", () => {
        expect(
            parseApiErrorMessage(
                {
                    message: "Validation failed.",
                    errors: {
                        coupon_code: ["Coupon is invalid."],
                    },
                },
                "Fallback error",
            ),
        ).toBe("Coupon is invalid.");
    });

    it("returns fallback when message cannot be extracted", () => {
        expect(parseApiErrorMessage({}, "Fallback error")).toBe("Fallback error");
    });
});
