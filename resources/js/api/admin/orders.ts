import { apiClient } from "@/api/client";
import { extractData, normalizeListResponse } from "@/api/response";
import {
    assertAdminOrderDetailWireDto,
    assertAdminOrderSummaryWireDto,
} from "@/contracts/api/v1/assertions/admin-orders";
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

interface ApiListRequestOptions {
    signal?: AbortSignal;
}

export const listAdminOrders = async (
    params: AdminOrderListParams,
    options?: ApiListRequestOptions,
): Promise<OrderListResponse> => {
    const { data } = await apiClient.get("/admin/orders", {
        params,
        signal: options?.signal,
    });

    const response = normalizeListResponse(data);

    return {
        data: mapAdminOrderListFromApi(
            response.data.map((item) => assertAdminOrderSummaryWireDto(item)),
        ),
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
    const response = extractData(data);

    if (response === null) {
        return null;
    }

    return mapAdminOrderDetailFromApi(assertAdminOrderDetailWireDto(response));
};

export const getAdminOrderDetail = async (orderId: string): Promise<AdminOrderDetail | null> => {
    const { data } = await apiClient.get(`/admin/orders/${orderId}`);
    const response = extractData(data);

    if (response === null) {
        return null;
    }

    return mapAdminOrderDetailFromApi(assertAdminOrderDetailWireDto(response));
};
