import { extractData, normalizeListResponse } from "@/api/response";
import { apiClient } from "@/api/client";
import {
    assertAccountOrdersSummaryWireDto,
    assertAccountOrderWireDtoList,
} from "@/contracts/api/v1/assertions/account-orders";
import { mapAccountOrderListFromApi } from "@/mappers/account/orders";
import type {
    AccountOrderListParams,
    AccountOrderListResponse,
    AccountOrdersSummary,
} from "@/types/account-orders";

export const listAccountOrders = async (
    params: AccountOrderListParams,
): Promise<AccountOrderListResponse> => {
    const { data } = await apiClient.get("/orders/me", {
        params,
    });
    const response = normalizeListResponse(data);

    return {
        data: mapAccountOrderListFromApi(assertAccountOrderWireDtoList(response.data)),
        meta: response.meta,
    };
};

export const getAccountOrdersSummary = async (): Promise<AccountOrdersSummary> => {
    const { data } = await apiClient.get("/orders/me/summary");
    const payload = assertAccountOrdersSummaryWireDto(extractData(data));

    return {
        total_orders: payload.total_orders,
        paid_orders: payload.paid_orders,
        in_delivery_orders: payload.in_delivery_orders,
        total_spent: payload.total_spent,
    };
};
