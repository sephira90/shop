import type {
    AddressPayload,
    AdminOrderDetail,
    AdminOrderSummary,
    OrderItem,
    OrderStatusUpdatePayload,
} from "@/types/admin-orders";

import { asArray, asRecord, toNumber, toNullableString, toString } from "@/mappers/common";

const mapAddress = (value: unknown): AddressPayload | null => {
    const record = asRecord(value);
    const line1 = toString(record.line1).trim();
    const city = toString(record.city).trim();
    const country = toString(record.country).trim();
    const postcode = toString(record.postcode).trim();

    if (line1 === "" && city === "" && country === "" && postcode === "") {
        return null;
    }

    const address: AddressPayload = {};

    if (line1 !== "") {
        address.line1 = line1;
    }
    if (city !== "") {
        address.city = city;
    }
    if (country !== "") {
        address.country = country;
    }
    if (postcode !== "") {
        address.postcode = postcode;
    }

    return address;
};

const mapOrderItem = (value: unknown): OrderItem => {
    const record = asRecord(value);

    return {
        sku: toString(record.sku),
        name: toString(record.name),
        quantity: toNumber(record.quantity),
        unit_price: toNumber(record.unit_price),
        total_price: toNumber(record.total_price),
    };
};

export const mapAdminOrderSummaryFromApi = (value: unknown): AdminOrderSummary => {
    const record = asRecord(value);

    return {
        id: toString(record.id),
        order_number: toString(record.order_number),
        email: toString(record.email),
        status: toString(record.status),
        payment_status: toString(record.payment_status),
        shipment_status: toString(record.shipment_status),
        currency: toString(record.currency, "USD"),
        total: toNumber(record.total),
        placed_at: toNullableString(record.placed_at),
        created_at: toNullableString(record.created_at),
    };
};

export const mapAdminOrderDetailFromApi = (value: unknown): AdminOrderDetail => {
    const record = asRecord(value);
    const summary = mapAdminOrderSummaryFromApi(record);

    return {
        ...summary,
        subtotal: toNumber(record.subtotal),
        billing_address: mapAddress(record.billing_address),
        shipping_address: mapAddress(record.shipping_address),
        items: asArray(record.items).map((item) => mapOrderItem(item)),
    };
};

export const mapAdminOrderListFromApi = (value: unknown): AdminOrderSummary[] => {
    return asArray(value).map((item) => mapAdminOrderSummaryFromApi(item));
};

export const toOrderStatusUpdateDto = (
    payload: OrderStatusUpdatePayload,
): OrderStatusUpdatePayload => {
    return {
        status: payload.status.trim(),
        payment_status: payload.payment_status.trim(),
        shipment_status: payload.shipment_status.trim(),
    };
};
