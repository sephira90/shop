import type {
    AccountOrderAddressWireDto,
    AccountOrderItemWireDto,
    AccountOrderWireDto,
} from "@/contracts/api/v1/account-orders";
import type { AccountOrder, AccountOrderAddress, AccountOrderItem } from "@/types/account-orders";

const mapAccountOrderItemFromApi = (value: AccountOrderItemWireDto): AccountOrderItem => {
    return {
        product_variant_id: value.product_variant_id,
        sku: value.sku ?? "",
        name: value.name ?? "",
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

    return {
        line1: value.line1 ?? undefined,
        city: value.city ?? undefined,
        country: value.country ?? undefined,
        postcode: value.postcode ?? undefined,
    };
};

export const mapAccountOrderFromApi = (value: AccountOrderWireDto): AccountOrder => {
    return {
        id: value.id,
        order_number: value.order_number,
        email: value.email,
        status: value.status,
        payment_status: value.payment_status,
        shipment_status: value.shipment_status,
        currency: value.currency,
        total: value.total,
        items: value.items.map((item) => mapAccountOrderItemFromApi(item)),
        billing_address: mapAccountOrderAddressFromApi(value.billing_address),
        shipping_address: mapAccountOrderAddressFromApi(value.shipping_address),
        placed_at: value.placed_at,
        created_at: value.created_at,
    };
};

export const mapAccountOrderListFromApi = (value: AccountOrderWireDto[]): AccountOrder[] => {
    return value.map((item) => mapAccountOrderFromApi(item));
};
