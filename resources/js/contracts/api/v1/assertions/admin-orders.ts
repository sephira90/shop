import { ApiContractError } from "@/api/response";
import type {
    AdminOrderAddressWireDto,
    AdminOrderDetailWireDto,
    AdminOrderItemWireDto,
    AdminOrderPaymentWireDto,
    AdminOrderShipmentWireDto,
    AdminOrderSummaryWireDto,
} from "@/contracts/api/v1/admin-orders";

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === "object" && value !== null && !Array.isArray(value);

const requireString = (record: Record<string, unknown>, key: string): string => {
    const value = record[key];

    if (typeof value !== "string") {
        throw new ApiContractError(`Order payload field \`${key}\` must be string.`);
    }

    return value;
};

const requireNumber = (record: Record<string, unknown>, key: string): number => {
    const value = Number(record[key]);

    if (!Number.isFinite(value)) {
        throw new ApiContractError(`Order payload field \`${key}\` must be number.`);
    }

    return value;
};

const parseNullableString = (record: Record<string, unknown>, key: string): string | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    if (typeof value === "string") {
        return value;
    }

    throw new ApiContractError(`Order payload field \`${key}\` must be string|null.`);
};

const parseAddress = (value: unknown): AdminOrderAddressWireDto | null => {
    if (value === null) {
        return null;
    }

    if (!isRecord(value)) {
        throw new ApiContractError("Order payload address field must be object|null.");
    }

    const address: AdminOrderAddressWireDto = {};

    if (Object.hasOwn(value, "line1")) {
        address.line1 = requireString(value, "line1");
    }
    if (Object.hasOwn(value, "city")) {
        address.city = requireString(value, "city");
    }
    if (Object.hasOwn(value, "country")) {
        address.country = requireString(value, "country");
    }
    if (Object.hasOwn(value, "postcode")) {
        address.postcode = requireString(value, "postcode");
    }

    return address;
};

const parseItems = (value: unknown): AdminOrderItemWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Order payload field `items` must be array.");
    }

    return value.map((item): AdminOrderItemWireDto => {
        if (!isRecord(item)) {
            throw new ApiContractError("Order item payload must be object.");
        }

        return {
            product_variant_id:
                item.product_variant_id === null ? null : requireNumber(item, "product_variant_id"),
            sku: requireString(item, "sku"),
            name: requireString(item, "name"),
            quantity: requireNumber(item, "quantity"),
            unit_price: requireNumber(item, "unit_price"),
            total_price: requireNumber(item, "total_price"),
        };
    });
};

const parsePayments = (value: unknown): AdminOrderPaymentWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Order payload field `payments` must be array.");
    }

    return value.map((item): AdminOrderPaymentWireDto => {
        if (!isRecord(item)) {
            throw new ApiContractError("Order payment payload must be object.");
        }

        return {
            gateway: requireString(item, "gateway"),
            transaction_id: requireString(item, "transaction_id"),
            status: parseNullableString(item, "status"),
            amount: requireNumber(item, "amount"),
        };
    });
};

const parseShipments = (value: unknown): AdminOrderShipmentWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Order payload field `shipments` must be array.");
    }

    return value.map((item): AdminOrderShipmentWireDto => {
        if (!isRecord(item)) {
            throw new ApiContractError("Order shipment payload must be object.");
        }

        return {
            provider: requireString(item, "provider"),
            tracking_number: requireString(item, "tracking_number"),
            status: parseNullableString(item, "status"),
            cost: requireNumber(item, "cost"),
        };
    });
};

export const assertAdminOrderSummaryWireDto = (value: unknown): AdminOrderSummaryWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Order summary payload must be an object.");
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
        placed_at: parseNullableString(value, "placed_at"),
        created_at: parseNullableString(value, "created_at"),
    };
};

export const assertAdminOrderDetailWireDto = (value: unknown): AdminOrderDetailWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Order detail payload must be an object.");
    }

    const summary = assertAdminOrderSummaryWireDto(value);

    return {
        ...summary,
        subtotal: requireNumber(value, "subtotal"),
        discount_total: requireNumber(value, "discount_total"),
        shipping_total: requireNumber(value, "shipping_total"),
        billing_address: parseAddress(value.billing_address),
        shipping_address: parseAddress(value.shipping_address),
        items: parseItems(value.items),
        payments: parsePayments(value.payments),
        shipments: parseShipments(value.shipments),
    };
};
