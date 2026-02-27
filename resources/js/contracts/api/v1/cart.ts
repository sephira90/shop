export interface CartItemWireDto {
    product_variant_id: number;
    sku: string | null;
    name: string | null;
    quantity: number;
    unit_price: number;
    line_total: number;
}

export interface CartSummaryWireDto {
    subtotal: number;
    discount_total: number;
    shipping_total: number;
    total: number;
}

export interface CartWireDto {
    id: string;
    guest_token: string | null;
    currency: string;
    status: string;
    items: CartItemWireDto[];
    summary: CartSummaryWireDto;
}

export interface CartUpsertItemRequestDto {
    product_variant_id: number;
    quantity: number;
    guest_token?: string;
}
