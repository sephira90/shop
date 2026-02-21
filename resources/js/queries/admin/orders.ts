import type { AdminOrderListParams } from '@/types/admin-orders';

export interface AdminOrderFilters {
    search: string;
    orderStatus: string;
    paymentStatus: string;
    shipmentStatus: string;
}

export const buildAdminOrderListParams = (page: number, filters: AdminOrderFilters): AdminOrderListParams => {
    const params: AdminOrderListParams = { page };
    const query = filters.search.trim();

    if (query !== '') {
        params.q = query;
    }

    if (filters.orderStatus !== 'all') {
        params.status = filters.orderStatus;
    }

    if (filters.paymentStatus !== 'all') {
        params.payment_status = filters.paymentStatus;
    }

    if (filters.shipmentStatus !== 'all') {
        params.shipment_status = filters.shipmentStatus;
    }

    return params;
};
