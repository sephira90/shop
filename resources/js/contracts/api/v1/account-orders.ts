export interface AccountOrderItemWireDto {
    product_variant_id: number;
    sku: string | null;
    name: string | null;
    quantity: number;
    unit_price: number;
    total_price: number;
}

export interface AccountOrderAddressWireDto {
    line1: string | null;
    city: string | null;
    country: string | null;
    postcode: string | null;
}

export interface AccountOrderWireDto {
    id: string;
    order_number: string;
    email: string;
    status: string;
    payment_status: string;
    shipment_status: string;
    currency: string;
    total: number;
    items: AccountOrderItemWireDto[];
    billing_address: AccountOrderAddressWireDto | null;
    shipping_address: AccountOrderAddressWireDto | null;
    placed_at: string | null;
    created_at: string | null;
}

export interface AccountOrdersSummaryWireDto {
    total_orders: number;
    paid_orders: number;
    in_delivery_orders: number;
    total_spent: number;
}
