import type { ListResponse } from '@/api/response';

export interface OrderItem {
    sku: string;
    name: string;
    quantity: number;
    unit_price: number;
    total_price: number;
}

export interface AddressPayload {
    line1?: string;
    city?: string;
    country?: string;
    postcode?: string;
}

export interface AdminOrderSummary {
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

export interface AdminOrderDetail extends AdminOrderSummary {
    subtotal: number;
    billing_address: AddressPayload | null;
    shipping_address: AddressPayload | null;
    items: OrderItem[];
    placed_at: string | null;
    created_at: string | null;
}

export type OrderListResponse = ListResponse<AdminOrderSummary>;

export interface AdminOrderListParams {
    page?: number;
    per_page?: number;
    q?: string;
    status?: string;
    payment_status?: string;
    shipment_status?: string;
}

export interface OrderStatusUpdatePayload {
    status: string;
    payment_status: string;
    shipment_status: string;
}
