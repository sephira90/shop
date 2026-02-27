import { ApiContractError } from "@/api/response";
import type { CartItemWireDto, CartSummaryWireDto, CartWireDto } from "@/contracts/api/v1/cart";

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === "object" && value !== null && !Array.isArray(value);

const requireString = (record: Record<string, unknown>, key: string): string => {
    const value = record[key];

    if (typeof value !== "string") {
        throw new ApiContractError(`Cart payload field \`${key}\` must be string.`);
    }

    return value;
};

const requireNullableString = (record: Record<string, unknown>, key: string): string | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    if (typeof value !== "string") {
        throw new ApiContractError(`Cart payload field \`${key}\` must be string|null.`);
    }

    return value;
};

const requireNumber = (record: Record<string, unknown>, key: string): number => {
    const value = Number(record[key]);

    if (!Number.isFinite(value)) {
        throw new ApiContractError(`Cart payload field \`${key}\` must be number.`);
    }

    return value;
};

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
            sku: requireNullableString(item, "sku"),
            name: requireNullableString(item, "name"),
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
        guest_token: requireNullableString(value, "guest_token"),
        currency: requireString(value, "currency"),
        status: requireString(value, "status"),
        items: parseItems(value.items),
        summary: parseSummary(value.summary),
    };
};
