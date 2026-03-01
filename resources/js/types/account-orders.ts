import type { ListResponse } from "@/api/response";

export type AccountOrderStatus =
    | "pending"
    | "paid"
    | "processing"
    | "shipped"
    | "completed"
    | "cancelled"
    | "refunded";

export type AccountPaymentStatus = "pending" | "authorized" | "captured" | "failed" | "refunded";

export type AccountShipmentStatus = "pending" | "packed" | "shipped" | "delivered" | "returned";

export interface AccountOrderItem {
    product_variant_id: number | null;
    sku: string;
    name: string;
    quantity: number;
    unit_price: number;
    total_price: number;
}

export interface AccountOrderPayment {
    gateway: string;
    transaction_id: string;
    status: AccountPaymentStatus | null;
    amount: number;
}

export interface AccountOrderShipment {
    provider: string;
    tracking_number: string;
    status: AccountShipmentStatus | null;
    cost: number;
}

export interface AccountOrderAddress {
    line1?: string;
    city?: string;
    country?: string;
    postcode?: string;
}

export interface AccountOrderSummary {
    id: string;
    order_number: string;
    email: string;
    status: AccountOrderStatus;
    payment_status: AccountPaymentStatus;
    shipment_status: AccountShipmentStatus;
    currency: string;
    total: number;
    placed_at: string | null;
    created_at: string | null;
}

export interface AccountOrderDetail extends AccountOrderSummary {
    subtotal: number;
    discount_total: number;
    shipping_total: number;
    billing_address: AccountOrderAddress | null;
    shipping_address: AccountOrderAddress | null;
    items: AccountOrderItem[];
    payments: AccountOrderPayment[];
    shipments: AccountOrderShipment[];
}

export type AccountOrderListResponse = ListResponse<AccountOrderSummary>;

export interface AccountOrderSummaryListParams {
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
