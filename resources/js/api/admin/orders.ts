import { apiClient } from "@/api/client";
import { extractData, normalizeListResponse } from "@/api/response";
import {
    mapAdminOrderDetailFromApi,
    mapAdminOrderListFromApi,
    toOrderStatusUpdateDto,
} from "@/mappers/admin/orders";
import type {
    AdminOrderDetail,
    AdminOrderListParams,
    OrderListResponse,
    OrderStatusUpdatePayload,
} from "@/types/admin-orders";

export const listAdminOrders = async (params: AdminOrderListParams): Promise<OrderListResponse> => {
    const { data } = await apiClient.get("/admin/orders", {
        params,
    });

    const response = normalizeListResponse<unknown>(data);

    return {
        data: mapAdminOrderListFromApi(response.data),
        meta: response.meta,
    };
};

export const updateAdminOrderStatus = async (
    orderId: string,
    payload: OrderStatusUpdatePayload,
): Promise<AdminOrderDetail | null> => {
    const { data } = await apiClient.patch(
        `/admin/orders/${orderId}/status`,
        toOrderStatusUpdateDto(payload),
    );
    const response = extractData<unknown>(data);

    return response ? mapAdminOrderDetailFromApi(response) : null;
};

export const getAdminOrderDetail = async (orderId: string): Promise<AdminOrderDetail | null> => {
    const { data } = await apiClient.get(`/admin/orders/${orderId}`);
    const response = extractData<unknown>(data);

    return response ? mapAdminOrderDetailFromApi(response) : null;
};
