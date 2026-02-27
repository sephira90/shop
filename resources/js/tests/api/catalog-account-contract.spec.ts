import { describe, expect, it } from "vitest";

import {
    assertAccountOrdersSummaryWireDto,
    assertAccountOrderWireDto,
} from "@/contracts/api/v1/assertions/account-orders";
import {
    assertCatalogProductVariantWireDto,
    assertCatalogProductWireDto,
} from "@/contracts/api/v1/assertions/catalog";

describe("catalog and account dto contract assertions", () => {
    it("parses catalog product payload", () => {
        const product = assertCatalogProductWireDto({
            id: 22,
            name: "Sneakers",
            slug: "sneakers",
            short_description: "Light",
            description: null,
            variants: [
                {
                    id: 101,
                    sku: "SN-101",
                    name: "Sneakers / 42",
                    price: 99.9,
                    currency: "USD",
                    is_active: true,
                },
            ],
        });

        expect(product.slug).toBe("sneakers");
        expect(product.variants[0].price).toBe(99.9);
    });

    it("parses account order payload and summary payload", () => {
        const order = assertAccountOrderWireDto({
            id: "ord-11",
            order_number: "ORD-1001",
            email: "buyer@example.com",
            status: "pending",
            payment_status: "pending",
            shipment_status: "pending",
            currency: "USD",
            total: 150,
            items: [
                {
                    product_variant_id: 3,
                    sku: "SKU-3",
                    name: "Variant 3",
                    quantity: 2,
                    unit_price: 75,
                    total_price: 150,
                },
            ],
            billing_address: {
                line1: "Main st 1",
                city: "NY",
                country: "US",
                postcode: "10001",
            },
            shipping_address: null,
            placed_at: null,
            created_at: "2026-02-27T10:00:00Z",
        });
        const summary = assertAccountOrdersSummaryWireDto({
            total_orders: 10,
            paid_orders: 4,
            in_delivery_orders: 3,
            total_spent: 1234.5,
        });

        expect(order.items[0].quantity).toBe(2);
        expect(summary.total_spent).toBe(1234.5);
    });

    it("rejects malformed catalog/account payloads", () => {
        expect(() =>
            assertCatalogProductVariantWireDto({
                id: 1,
                sku: "SKU-1",
                name: "Variant",
                price: "99.9",
                currency: "USD",
                is_active: "yes",
            }),
        ).toThrowError(/must be boolean/);

        expect(() =>
            assertAccountOrderWireDto({
                id: "ord-11",
                order_number: "ORD-1001",
                email: "buyer@example.com",
                status: "pending",
                payment_status: "pending",
                shipment_status: "pending",
                currency: "USD",
                total: 150,
                items: {},
                billing_address: null,
                shipping_address: null,
                placed_at: null,
                created_at: null,
            }),
        ).toThrowError(/`items` must be array/);
    });
});
