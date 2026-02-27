export interface AdminOrderSummaryWireDto {
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

export interface AdminOrderAddressWireDto {
    line1?: string;
    city?: string;
    country?: string;
    postcode?: string;
}

export interface AdminOrderItemWireDto {
    product_variant_id: number | null;
    sku: string;
    name: string;
    quantity: number;
    unit_price: number;
    total_price: number;
}

export interface AdminOrderPaymentWireDto {
    gateway: string;
    transaction_id: string;
    status: string | null;
    amount: number;
}

export interface AdminOrderShipmentWireDto {
    provider: string;
    tracking_number: string;
    status: string | null;
    cost: number;
}

export interface AdminOrderDetailWireDto extends AdminOrderSummaryWireDto {
    subtotal: number;
    discount_total: number;
    shipping_total: number;
    billing_address: AdminOrderAddressWireDto | null;
    shipping_address: AdminOrderAddressWireDto | null;
    items: AdminOrderItemWireDto[];
    payments: AdminOrderPaymentWireDto[];
    shipments: AdminOrderShipmentWireDto[];
}
