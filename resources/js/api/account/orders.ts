import { extractData, normalizeListResponse } from "@/api/response";
import { apiClient } from "@/api/client";
import {
    assertAccountOrderDetailWireDto,
    assertAccountOrderSummaryWireDtoList,
    assertAccountOrdersSummaryWireDto,
} from "@/contracts/api/v1/assertions/account-orders";
import { mapAccountOrderDetailFromApi, mapAccountOrderListFromApi } from "@/mappers/account/orders";
import type {
    AccountOrderDetail,
    AccountOrderListResponse,
    AccountOrderSummaryListParams,
    AccountOrdersSummary,
} from "@/types/account-orders";

interface ApiDetailRequestOptions {
    signal?: AbortSignal;
}

export const listAccountOrders = async (
    params: AccountOrderSummaryListParams,
): Promise<AccountOrderListResponse> => {
    const { data } = await apiClient.get("/account/orders", {
        params,
    });
    const response = normalizeListResponse(data);

    return {
        data: mapAccountOrderListFromApi(assertAccountOrderSummaryWireDtoList(response.data)),
        meta: response.meta,
    };
};

export const getAccountOrderDetail = async (
    orderId: string,
    options?: ApiDetailRequestOptions,
): Promise<AccountOrderDetail | null> => {
    const { data } = await apiClient.get(`/account/orders/${orderId}`, {
        signal: options?.signal,
    });
    const response = extractData(data);

    if (response === null) {
        return null;
    }

    return mapAccountOrderDetailFromApi(assertAccountOrderDetailWireDto(response));
};

export const getAccountOrdersSummary = async (): Promise<AccountOrdersSummary> => {
    const { data } = await apiClient.get("/account/orders/summary");
    const payload = assertAccountOrdersSummaryWireDto(extractData(data));

    return {
        total_orders: payload.total_orders,
        paid_orders: payload.paid_orders,
        in_delivery_orders: payload.in_delivery_orders,
        total_spent: payload.total_spent,
    };
};
