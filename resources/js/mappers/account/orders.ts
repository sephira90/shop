import type {
    AccountOrderDetailWireDto,
    AccountOrderItemWireDto,
    AccountOrderPaymentWireDto,
    AccountOrderShipmentWireDto,
    AccountOrderSummaryWireDto,
} from "@/contracts/api/v1/account-orders";
import { mapOptionalAddressFields } from "@/mappers/common";
import type {
    AccountOrderDetail,
    AccountOrderItem,
    AccountOrderStatus,
    AccountOrderPayment,
    AccountPaymentStatus,
    AccountOrderShipment,
    AccountShipmentStatus,
    AccountOrderSummary,
} from "@/types/account-orders";

const mapAccountOrderItemFromApi = (value: AccountOrderItemWireDto): AccountOrderItem => {
    return {
        product_variant_id: value.product_variant_id,
        sku: value.sku,
        name: value.name,
        quantity: value.quantity,
        unit_price: value.unit_price,
        total_price: value.total_price,
    };
};

const mapAccountOrderPaymentFromApi = (value: AccountOrderPaymentWireDto): AccountOrderPayment => {
    return {
        gateway: value.gateway,
        transaction_id: value.transaction_id,
        status: value.status as AccountPaymentStatus | null,
        amount: value.amount,
    };
};

const mapAccountOrderShipmentFromApi = (
    value: AccountOrderShipmentWireDto,
): AccountOrderShipment => {
    return {
        provider: value.provider,
        tracking_number: value.tracking_number,
        status: value.status as AccountShipmentStatus | null,
        cost: value.cost,
    };
};

export const mapAccountOrderSummaryFromApi = (
    value: AccountOrderSummaryWireDto,
): AccountOrderSummary => {
    return {
        id: value.id,
        order_number: value.order_number,
        email: value.email,
        status: value.status as AccountOrderStatus,
        payment_status: value.payment_status as AccountPaymentStatus,
        shipment_status: value.shipment_status as AccountShipmentStatus,
        currency: value.currency,
        total: value.total,
        placed_at: value.placed_at,
        created_at: value.created_at,
    };
};

export const mapAccountOrderDetailFromApi = (
    value: AccountOrderDetailWireDto,
): AccountOrderDetail => {
    const summary = mapAccountOrderSummaryFromApi(value);

    return {
        ...summary,
        subtotal: value.subtotal,
        discount_total: value.discount_total,
        shipping_total: value.shipping_total,
        billing_address: mapOptionalAddressFields(value.billing_address),
        shipping_address: mapOptionalAddressFields(value.shipping_address),
        items: value.items.map((item) => mapAccountOrderItemFromApi(item)),
        payments: value.payments.map((payment) => mapAccountOrderPaymentFromApi(payment)),
        shipments: value.shipments.map((shipment) => mapAccountOrderShipmentFromApi(shipment)),
    };
};

export const mapAccountOrderListFromApi = (
    value: AccountOrderSummaryWireDto[],
): AccountOrderSummary[] => {
    return value.map((item) => mapAccountOrderSummaryFromApi(item));
};
