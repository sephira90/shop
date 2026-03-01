export interface AccountOrderSummaryWireDto {
    id: string;
    order_number: string;
    email: string;
    status: string;
    payment_status: string;
    shipment_status: string;
    currency: string;
    total: number;
    placed_at: string | null;
    created_at: string | null;
}

export interface AccountOrderItemWireDto {
    product_variant_id: number | null;
    sku: string;
    name: string;
    quantity: number;
    unit_price: number;
    total_price: number;
}

export interface AccountOrderAddressWireDto {
    line1?: string;
    city?: string;
    country?: string;
    postcode?: string;
}

export interface AccountOrderPaymentWireDto {
    gateway: string;
    transaction_id: string;
    status: string | null;
    amount: number;
}

export interface AccountOrderShipmentWireDto {
    provider: string;
    tracking_number: string;
    status: string | null;
    cost: number;
}

export interface AccountOrderDetailWireDto extends AccountOrderSummaryWireDto {
    subtotal: number;
    discount_total: number;
    shipping_total: number;
    billing_address: AccountOrderAddressWireDto | null;
    shipping_address: AccountOrderAddressWireDto | null;
    items: AccountOrderItemWireDto[];
    payments: AccountOrderPaymentWireDto[];
    shipments: AccountOrderShipmentWireDto[];
}

export interface AccountOrdersSummaryWireDto {
    total_orders: number;
    paid_orders: number;
    in_delivery_orders: number;
    total_spent: number;
}
