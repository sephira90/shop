import { ApiContractError } from "@/api/response";
import type {
    AccountOrderAddressWireDto,
    AccountOrderItemWireDto,
    AccountOrdersSummaryWireDto,
    AccountOrderWireDto,
} from "@/contracts/api/v1/account-orders";

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === "object" && value !== null && !Array.isArray(value);

const requireString = (record: Record<string, unknown>, key: string): string => {
    const value = record[key];

    if (typeof value !== "string") {
        throw new ApiContractError(`Account order payload field \`${key}\` must be string.`);
    }

    return value;
};

const requireNumber = (record: Record<string, unknown>, key: string): number => {
    const value = Number(record[key]);

    if (!Number.isFinite(value)) {
        throw new ApiContractError(`Account order payload field \`${key}\` must be number.`);
    }

    return value;
};

const parseNullableString = (record: Record<string, unknown>, key: string): string | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    if (typeof value !== "string") {
        throw new ApiContractError(`Account order payload field \`${key}\` must be string|null.`);
    }

    return value;
};

const parseAddress = (value: unknown): AccountOrderAddressWireDto | null => {
    if (value === null) {
        return null;
    }

    if (!isRecord(value)) {
        throw new ApiContractError("Account order address payload must be object|null.");
    }

    return {
        line1: parseNullableString(value, "line1"),
        city: parseNullableString(value, "city"),
        country: parseNullableString(value, "country"),
        postcode: parseNullableString(value, "postcode"),
    };
};

const parseItem = (value: unknown): AccountOrderItemWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Account order item payload must be an object.");
    }

    return {
        product_variant_id: requireNumber(value, "product_variant_id"),
        sku: parseNullableString(value, "sku"),
        name: parseNullableString(value, "name"),
        quantity: requireNumber(value, "quantity"),
        unit_price: requireNumber(value, "unit_price"),
        total_price: requireNumber(value, "total_price"),
    };
};

const parseItems = (value: unknown): AccountOrderItemWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Account order payload field `items` must be array.");
    }

    return value.map((item) => parseItem(item));
};

export const assertAccountOrderWireDto = (value: unknown): AccountOrderWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Account order payload must be an object.");
    }

    return {
        id: requireString(value, "id"),
        order_number: requireString(value, "order_number"),
        email: requireString(value, "email"),
        status: requireString(value, "status"),
        payment_status: requireString(value, "payment_status"),
        shipment_status: requireString(value, "shipment_status"),
        currency: requireString(value, "currency"),
        total: requireNumber(value, "total"),
        items: parseItems(value.items),
        billing_address: parseAddress(value.billing_address),
        shipping_address: parseAddress(value.shipping_address),
        placed_at: parseNullableString(value, "placed_at"),
        created_at: parseNullableString(value, "created_at"),
    };
};

export const assertAccountOrderWireDtoList = (value: unknown): AccountOrderWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Account order list payload must be array.");
    }

    return value.map((item) => assertAccountOrderWireDto(item));
};

export const assertAccountOrdersSummaryWireDto = (value: unknown): AccountOrdersSummaryWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Account orders summary payload must be an object.");
    }

    return {
        total_orders: requireNumber(value, "total_orders"),
        paid_orders: requireNumber(value, "paid_orders"),
        in_delivery_orders: requireNumber(value, "in_delivery_orders"),
        total_spent: requireNumber(value, "total_spent"),
    };
};
