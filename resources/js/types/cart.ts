export interface CartItem {
    product_variant_id: number;
    sku: string;
    name: string;
    quantity: number;
    unit_price: number;
    line_total: number;
}

export interface CartSummary {
    subtotal: number;
    total: number;
    shipping_total: number;
    discount_total: number;
}

export interface CartPayload {
    id: string;
    guest_token: string | null;
    currency: string;
    status: string;
    items: CartItem[];
    summary: CartSummary;
}

export interface CartUpsertItemPayload {
    product_variant_id: number;
    quantity: number;
    guest_token?: string;
}
