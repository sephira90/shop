import type { AccountOrder, AccountOrderAddress, AccountOrderItem } from '@/types/account-orders';

import { asArray, asRecord, toInteger, toNullableString, toNumber, toString } from '@/mappers/common';

const mapAccountOrderItemFromApi = (value: unknown): AccountOrderItem => {
    const record = asRecord(value);

    return {
        product_variant_id: toInteger(record.product_variant_id),
        sku: toString(record.sku),
        name: toString(record.name),
        quantity: toInteger(record.quantity),
        unit_price: toNumber(record.unit_price),
        total_price: toNumber(record.total_price),
    };
};

const mapAccountOrderAddressFromApi = (value: unknown): AccountOrderAddress | null => {
    const record = asRecord(value);

    if (Object.keys(record).length === 0) {
        return null;
    }

    return {
        line1: toNullableString(record.line1) ?? undefined,
        city: toNullableString(record.city) ?? undefined,
        country: toNullableString(record.country) ?? undefined,
        postcode: toNullableString(record.postcode) ?? undefined,
    };
};

export const mapAccountOrderFromApi = (value: unknown): AccountOrder => {
    const record = asRecord(value);

    return {
        id: toString(record.id),
        order_number: toString(record.order_number),
        email: toString(record.email),
        status: toString(record.status),
        payment_status: toString(record.payment_status),
        shipment_status: toString(record.shipment_status),
        currency: toString(record.currency, 'USD'),
        total: toNumber(record.total),
        items: asArray(record.items).map((item) => mapAccountOrderItemFromApi(item)),
        billing_address: mapAccountOrderAddressFromApi(record.billing_address),
        shipping_address: mapAccountOrderAddressFromApi(record.shipping_address),
        placed_at: toNullableString(record.placed_at),
        created_at: toNullableString(record.created_at),
    };
};

export const mapAccountOrderListFromApi = (value: unknown): AccountOrder[] => {
    return asArray(value).map((item) => mapAccountOrderFromApi(item));
};
