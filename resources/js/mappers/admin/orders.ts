import type {
    AdminOrderDetailWireDto,
    AdminOrderItemWireDto,
    AdminOrderSummaryWireDto,
} from "@/contracts/api/v1/admin-orders";
import { mapOptionalAddressFields } from "@/mappers/common";
import type {
    AdminOrderDetail,
    AdminOrderSummary,
    OrderItem,
    OrderStatusUpdatePayload,
} from "@/types/admin-orders";

const mapOrderItem = (value: AdminOrderItemWireDto): OrderItem => {
    return {
        sku: value.sku,
        name: value.name,
        quantity: value.quantity,
        unit_price: value.unit_price,
        total_price: value.total_price,
    };
};

export const mapAdminOrderSummaryFromApi = (value: AdminOrderSummaryWireDto): AdminOrderSummary => {
    return {
        id: value.id,
        order_number: value.order_number,
        email: value.email,
        status: value.status,
        payment_status: value.payment_status,
        shipment_status: value.shipment_status,
        currency: value.currency,
        total: value.total,
        placed_at: value.placed_at,
        created_at: value.created_at,
    };
};

export const mapAdminOrderDetailFromApi = (value: AdminOrderDetailWireDto): AdminOrderDetail => {
    const summary = mapAdminOrderSummaryFromApi(value);

    return {
        ...summary,
        subtotal: value.subtotal,
        billing_address: mapOptionalAddressFields(value.billing_address),
        shipping_address: mapOptionalAddressFields(value.shipping_address),
        items: value.items.map((item) => mapOrderItem(item)),
    };
};

export const mapAdminOrderListFromApi = (
    value: AdminOrderSummaryWireDto[],
): AdminOrderSummary[] => {
    return value.map((item) => mapAdminOrderSummaryFromApi(item));
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
