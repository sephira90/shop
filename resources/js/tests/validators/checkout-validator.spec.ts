import { describe, expect, it } from "vitest";

import {
    buildCheckoutIdempotencyKey,
    buildCheckoutPayload,
    createCheckoutFormState,
} from "@/validators/checkout";

describe("checkout validator", () => {
    it("creates default form state", () => {
        expect(createCheckoutFormState(" customer@example.com ")).toEqual({
            email: "customer@example.com",
            coupon_code: "",
            billing_address: {
                line1: "",
                city: "",
                country: "US",
                postcode: "",
            },
            shipping_address: {
                line1: "",
                city: "",
                country: "US",
                postcode: "",
            },
        });
    });

    it("builds normalized payload with optional guest token", () => {
        const payload = buildCheckoutPayload(
            {
                email: " buyer@example.com ",
                coupon_code: "  VIP10  ",
                billing_address: {
                    line1: "  Main st 1 ",
                    city: "  New York ",
                    country: " us ",
                    postcode: " 10001 ",
                },
                shipping_address: {
                    line1: "  Main st 2 ",
                    city: "  New York ",
                    country: " us ",
                    postcode: " 10002 ",
                },
            },
            " guest-123 ",
        );

        expect(payload).toEqual({
            guest_token: "guest-123",
            email: "buyer@example.com",
            coupon_code: "VIP10",
            billing_address: {
                line1: "Main st 1",
                city: "New York",
                country: "US",
                postcode: "10001",
            },
            shipping_address: {
                line1: "Main st 2",
                city: "New York",
                country: "US",
                postcode: "10002",
            },
        });
    });

    it("omits guest token and empty coupon code", () => {
        const payload = buildCheckoutPayload(
            {
                email: "buyer@example.com",
                coupon_code: "   ",
                billing_address: {
                    line1: "Main st 1",
                    city: "New York",
                    country: "US",
                    postcode: "10001",
                },
                shipping_address: {
                    line1: "Main st 2",
                    city: "New York",
                    country: "US",
                    postcode: "10002",
                },
            },
            null,
        );

        expect(payload).toEqual({
            email: "buyer@example.com",
            coupon_code: null,
            billing_address: {
                line1: "Main st 1",
                city: "New York",
                country: "US",
                postcode: "10001",
            },
            shipping_address: {
                line1: "Main st 2",
                city: "New York",
                country: "US",
                postcode: "10002",
            },
        });
    });

    it("generates non-empty idempotency keys", () => {
        const first = buildCheckoutIdempotencyKey();
        const second = buildCheckoutIdempotencyKey();

        expect(first.length).toBeGreaterThan(10);
        expect(second.length).toBeGreaterThan(10);
        expect(first).not.toBe(second);
    });
});
