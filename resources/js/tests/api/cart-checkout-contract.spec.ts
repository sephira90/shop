import { describe, expect, it } from "vitest";

import { assertCartWireDto } from "@/contracts/api/v1/assertions/cart";
import { assertCheckoutOrderWireDto } from "@/contracts/api/v1/assertions/checkout";

describe("cart and checkout dto contract assertions", () => {
    it("parses cart payload", () => {
        const cart = assertCartWireDto({
            id: "cart-10",
            guest_token: "guest-10",
            currency: "USD",
            status: "active",
            items: [
                {
                    product_variant_id: 101,
                    sku: "SKU-101",
                    name: "Variant 101",
                    quantity: 2,
                    unit_price: 15.5,
                    line_total: 31,
                },
            ],
            summary: {
                subtotal: 31,
                discount_total: 0,
                shipping_total: 0,
                total: 31,
            },
        });

        expect(cart.id).toBe("cart-10");
        expect(cart.items[0].quantity).toBe(2);
    });

    it("parses checkout order payload", () => {
        const order = assertCheckoutOrderWireDto({
            id: "ord-501",
            order_number: "ORD-5001",
            payment: {
                payment_id: 7001,
                transaction_id: "txn-7001",
                status: "pending",
                payload: {
                    gateway: "fake",
                },
            },
        });

        expect(order.order_number).toBe("ORD-5001");
        expect(order.payment.payment_id).toBe(7001);
    });

    it("rejects malformed cart payload", () => {
        expect(() =>
            assertCartWireDto({
                id: "cart-1",
                guest_token: null,
                currency: "USD",
                status: "active",
                items: {},
                summary: {
                    subtotal: 0,
                    discount_total: 0,
                    shipping_total: 0,
                    total: 0,
                },
            }),
        ).toThrowError(/`items` must be array/);
    });
});
