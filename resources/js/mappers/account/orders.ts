import type {
    AccountOrderAddressWireDto,
    AccountOrderDetailWireDto,
    AccountOrderItemWireDto,
    AccountOrderPaymentWireDto,
    AccountOrderShipmentWireDto,
    AccountOrderSummaryWireDto,
} from "@/contracts/api/v1/account-orders";
import type {
    AccountOrderAddress,
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

const mapAccountOrderAddressFromApi = (
    value: AccountOrderAddressWireDto | null,
): AccountOrderAddress | null => {
    if (value === null) {
        return null;
    }

    const line1 = (value.line1 ?? "").trim();
    const city = (value.city ?? "").trim();
    const country = (value.country ?? "").trim();
    const postcode = (value.postcode ?? "").trim();

    if (line1 === "" && city === "" && country === "" && postcode === "") {
        return null;
    }

    const address: AccountOrderAddress = {};

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
        billing_address: mapAccountOrderAddressFromApi(value.billing_address),
        shipping_address: mapAccountOrderAddressFromApi(value.shipping_address),
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
