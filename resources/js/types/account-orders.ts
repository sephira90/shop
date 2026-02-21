import type { ListResponse } from "@/api/response";

export interface AccountOrderItem {
    product_variant_id: number;
    sku: string;
    name: string;
    quantity: number;
    unit_price: number;
    total_price: number;
}

export interface AccountOrderAddress {
    line1?: string;
    city?: string;
    country?: string;
    postcode?: string;
}

export interface AccountOrder {
    id: string;
    order_number: string;
    email: string;
    status: string;
    payment_status: string;
    shipment_status: string;
    currency: string;
    total: number;
    items: AccountOrderItem[];
    billing_address: AccountOrderAddress | null;
    shipping_address: AccountOrderAddress | null;
    placed_at: string | null;
    created_at: string | null;
}

export type AccountOrderListResponse = ListResponse<AccountOrder>;

export interface AccountOrderListParams {
    page?: number;
    per_page?: number;
    q?: string;
    status?: Exclude<AccountOrderStatusFilter, "all">;
}

export interface AccountOrdersSummary {
    total_orders: number;
    paid_orders: number;
    in_delivery_orders: number;
    total_spent: number;
}

export type AccountOrderStatusFilter =
    | "all"
    | "pending"
    | "paid"
    | "processing"
    | "shipped"
    | "completed"
    | "cancelled"
    | "refunded";
