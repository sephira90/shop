import { ApiContractError } from "@/api/response";
import { createFieldParsers, isRecord } from "@/contracts/api/v1/assertions/primitives";
import type { CartItemWireDto, CartSummaryWireDto, CartWireDto } from "@/contracts/api/v1/cart";

const { parseNullableString, requireNumber, requireString } = createFieldParsers("Cart");

const parseSummary = (value: unknown): CartSummaryWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Cart payload field `summary` must be object.");
    }

    return {
        subtotal: requireNumber(value, "subtotal"),
        discount_total: requireNumber(value, "discount_total"),
        shipping_total: requireNumber(value, "shipping_total"),
        total: requireNumber(value, "total"),
    };
};

const parseItems = (value: unknown): CartItemWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Cart payload field `items` must be array.");
    }

    return value.map((item): CartItemWireDto => {
        if (!isRecord(item)) {
            throw new ApiContractError("Cart item payload must be object.");
        }

        return {
            product_variant_id: requireNumber(item, "product_variant_id"),
            sku: parseNullableString(item, "sku"),
            name: parseNullableString(item, "name"),
            quantity: requireNumber(item, "quantity"),
            unit_price: requireNumber(item, "unit_price"),
            line_total: requireNumber(item, "line_total"),
        };
    });
};

export const assertCartWireDto = (value: unknown): CartWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Cart payload must be an object.");
    }

    return {
        id: requireString(value, "id"),
        guest_token: parseNullableString(value, "guest_token"),
        currency: requireString(value, "currency"),
        status: requireString(value, "status"),
        items: parseItems(value.items),
        summary: parseSummary(value.summary),
    };
};
