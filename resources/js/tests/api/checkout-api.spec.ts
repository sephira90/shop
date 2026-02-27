import { describe, expect, it, vi } from "vitest";

vi.mock("@/api/client", () => ({
    apiClient: {
        post: vi.fn(),
    },
}));

import { apiClient } from "@/api/client";
import { placeCheckoutOrder } from "@/api/checkout";
import type { CheckoutPlaceOrderPayload } from "@/types/checkout";

const apiClientMock = apiClient as unknown as {
    post: ReturnType<typeof vi.fn>;
};

const payload: CheckoutPlaceOrderPayload = {
    guest_token: "guest-1",
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
};

describe("checkout api", () => {
    it("maps place-order response from unified data envelope", async () => {
        apiClientMock.post.mockResolvedValueOnce({
            data: {
                data: {
                    id: "ord-11",
                    order_number: "ORD-1001",
                    payment: {
                        payment_id: 41,
                        transaction_id: "txn-41",
                        status: "pending",
                        payload: {
                            gateway: "fake",
                        },
                    },
                },
            },
        });

        const result = await placeCheckoutOrder(payload, "idem-1");

        expect(apiClientMock.post).toHaveBeenCalledWith("/checkout/place-order", payload, {
            headers: {
                "Idempotency-Key": "idem-1",
            },
        });
        expect(result).toEqual({
            id: "ord-11",
            order_number: "ORD-1001",
            payment: {
                payment_id: 41,
                transaction_id: "txn-41",
                status: "pending",
                payload: {
                    gateway: "fake",
                },
            },
        });
    });

    it("throws when response does not match unified envelope", async () => {
        apiClientMock.post.mockResolvedValueOnce({
            data: {
                order_number: "ORD-1002",
                payment: {
                    payment_id: 42,
                    transaction_id: "txn-42",
                    status: "pending",
                    payload: {},
                },
            },
        });

        await expect(placeCheckoutOrder(payload, "idem-2")).rejects.toThrowError(
            "API response must contain `data`.",
        );
    });

    it("returns null when nested payment payload is invalid", async () => {
        apiClientMock.post.mockResolvedValueOnce({
            data: {
                data: {
                    id: "ord-13",
                    order_number: "ORD-1003",
                    payment: {
                        payment_id: 0,
                        transaction_id: "",
                        status: "",
                        payload: {},
                    },
                },
            },
        });

        const result = await placeCheckoutOrder(payload, "idem-3");

        expect(result).toBeNull();
    });
});
