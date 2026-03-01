import { describe, expect, it } from "vitest";

import {
    assertAdminCategoryOptionWireDto,
    assertAdminCategoryWireDto,
} from "@/contracts/api/v1/assertions/admin-categories";
import {
    assertAdminOrderDetailWireDto,
    assertAdminOrderSummaryWireDto,
} from "@/contracts/api/v1/assertions/admin-orders";
import { assertAdminProductWireDto } from "@/contracts/api/v1/assertions/admin-products";
import {
    assertPromotionCouponWireDto,
    assertPromotionWireDto,
} from "@/contracts/api/v1/assertions/admin-promotions";

describe("admin dto contract assertions", () => {
    it("parses category payload", () => {
        const category = assertAdminCategoryWireDto({
            id: 11,
            parent_id: null,
            name: "Shoes",
            slug: "shoes",
            description: null,
            meta_title: null,
            meta_description: null,
            is_active: true,
            sort_order: 10,
            parent: null,
            children_count: 1,
            products_count: 5,
        });

        expect(category.id).toBe(11);
        expect(category.slug).toBe("shoes");

        const option = assertAdminCategoryOptionWireDto({
            id: 12,
            parent_id: 11,
            name: "Boots",
            slug: "boots",
            is_active: false,
        });

        expect(option.parent_id).toBe(11);
        expect(option.is_active).toBe(false);
    });

    it("parses order summary and detail payload", () => {
        const summary = assertAdminOrderSummaryWireDto({
            id: "ord-1",
            order_number: "ORD-1001",
            email: "john@example.com",
            status: "pending",
            payment_status: "pending",
            shipment_status: "pending",
            currency: "USD",
            total: 199.99,
            placed_at: null,
            created_at: null,
        });
        const detail = assertAdminOrderDetailWireDto({
            ...summary,
            subtotal: 199.99,
            discount_total: 0,
            shipping_total: 0,
            billing_address: null,
            shipping_address: null,
            items: [],
            payments: [],
            shipments: [],
        });

        expect(summary.order_number).toBe("ORD-1001");
        expect(detail.subtotal).toBe(199.99);
    });

    it("parses promotion and coupon payload", () => {
        const promotion = assertPromotionWireDto({
            id: 7,
            name: "Spring",
            code: "SPRING10",
            type: "percent",
            value: 10,
            is_active: true,
            usage_limit: null,
            usage_count: 0,
            starts_at: null,
            ends_at: null,
            coupons: [
                {
                    id: 70,
                    code: "SPRING10",
                    is_active: true,
                    max_redemptions: null,
                    redeemed_count: 0,
                    expires_at: null,
                },
            ],
        });
        const coupon = assertPromotionCouponWireDto({
            id: 70,
            code: "SPRING10",
            is_active: true,
            max_redemptions: null,
            redeemed_count: 0,
            expires_at: null,
        });

        expect(promotion.type).toBe("percent");
        expect(coupon.code).toBe("SPRING10");
    });

    it("parses product payload", () => {
        const product = assertAdminProductWireDto({
            id: 15,
            sku: "SKU-15",
            name: "Running Shoes",
            slug: "running-shoes",
            short_description: null,
            description: "desc",
            status: "active",
            is_featured: true,
            brand: "Brand",
            weight_grams: 500,
            category: {
                id: 3,
                name: "Shoes",
                slug: "shoes",
            },
            meta: {
                title: "Meta",
                description: null,
            },
            variants: [
                {
                    id: 150,
                    sku: "SKU-15-RED-42",
                    name: "Red 42",
                    attributes: {
                        color: "red",
                    },
                    price: 99.99,
                    compare_at_price: null,
                    currency: "USD",
                    is_active: true,
                    inventory: {
                        quantity: 7,
                        reserved_quantity: 2,
                        available_quantity: 5,
                    },
                },
            ],
            published_at: null,
            created_at: null,
            updated_at: null,
        });

        expect(product.name).toBe("Running Shoes");
        expect(product.variants[0].inventory?.available_quantity).toBe(5);
    });

    it("rejects malformed admin payload", () => {
        expect(() =>
            assertPromotionWireDto({
                id: 7,
                name: "Spring",
                code: "SPRING10",
                type: "coupon",
            }),
        ).toThrowError(/must be 'percent'\|'fixed'/);

        expect(() =>
            assertAdminProductWireDto({
                id: 1,
                sku: "SKU-1",
                name: "Product",
                slug: "product",
                short_description: null,
                description: null,
                status: "enabled",
                is_featured: true,
                brand: null,
                weight_grams: null,
                category: null,
                meta: {
                    title: null,
                    description: null,
                },
                variants: [],
                published_at: null,
                created_at: null,
                updated_at: null,
            }),
        ).toThrowError(/must be 'draft'\|'active'\|'archived'/);
    });
});
