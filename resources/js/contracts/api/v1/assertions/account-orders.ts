import { ApiContractError } from "@/api/response";
import { createFieldParsers, isRecord } from "@/contracts/api/v1/assertions/primitives";
import type {
    AccountOrderAddressWireDto,
    AccountOrderDetailWireDto,
    AccountOrderItemWireDto,
    AccountOrderPaymentWireDto,
    AccountOrderShipmentWireDto,
    AccountOrdersSummaryWireDto,
    AccountOrderSummaryWireDto,
} from "@/contracts/api/v1/account-orders";

const { parseNullableString, requireNumber, requireString } = createFieldParsers("Account order");

const parseAddress = (value: unknown): AccountOrderAddressWireDto | null => {
    if (value === null) {
        return null;
    }

    if (!isRecord(value)) {
        throw new ApiContractError("Account order address payload must be object|null.");
    }

    const address: AccountOrderAddressWireDto = {};

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

const parseItem = (value: unknown): AccountOrderItemWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Account order item payload must be an object.");
    }

    return {
        product_variant_id:
            value.product_variant_id === null ? null : requireNumber(value, "product_variant_id"),
        sku: requireString(value, "sku"),
        name: requireString(value, "name"),
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

const parsePayments = (value: unknown): AccountOrderPaymentWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Account order payload field `payments` must be array.");
    }

    return value.map((item): AccountOrderPaymentWireDto => {
        if (!isRecord(item)) {
            throw new ApiContractError("Account order payment payload must be an object.");
        }

        return {
            gateway: requireString(item, "gateway"),
            transaction_id: requireString(item, "transaction_id"),
            status: parseNullableString(item, "status"),
            amount: requireNumber(item, "amount"),
        };
    });
};

const parseShipments = (value: unknown): AccountOrderShipmentWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Account order payload field `shipments` must be array.");
    }

    return value.map((item): AccountOrderShipmentWireDto => {
        if (!isRecord(item)) {
            throw new ApiContractError("Account order shipment payload must be an object.");
        }

        return {
            provider: requireString(item, "provider"),
            tracking_number: requireString(item, "tracking_number"),
            status: parseNullableString(item, "status"),
            cost: requireNumber(item, "cost"),
        };
    });
};

export const assertAccountOrderSummaryWireDto = (value: unknown): AccountOrderSummaryWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Account order summary payload must be an object.");
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

export const assertAccountOrderDetailWireDto = (value: unknown): AccountOrderDetailWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Account order detail payload must be an object.");
    }

    const summary = assertAccountOrderSummaryWireDto(value);

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

export const assertAccountOrderSummaryWireDtoList = (
    value: unknown,
): AccountOrderSummaryWireDto[] => {
    if (!Array.isArray(value)) {
        throw new ApiContractError("Account order summary list payload must be array.");
    }

    return value.map((item) => assertAccountOrderSummaryWireDto(item));
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
