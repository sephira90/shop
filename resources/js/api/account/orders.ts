import { extractData, normalizeListResponse } from "@/api/response";
import { apiClient } from "@/api/client";
import { asRecord, toInteger, toNumber } from "@/mappers/common";
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
    const response = normalizeListResponse<unknown>(data);

    return {
        data: mapAccountOrderListFromApi(response.data),
        meta: response.meta,
    };
};

export const getAccountOrdersSummary = async (): Promise<AccountOrdersSummary> => {
    const { data } = await apiClient.get("/orders/me/summary");
    const payload = asRecord(extractData<unknown>(data));

    return {
        total_orders: toInteger(payload.total_orders, 0),
        paid_orders: toInteger(payload.paid_orders, 0),
        in_delivery_orders: toInteger(payload.in_delivery_orders, 0),
        total_spent: toNumber(payload.total_spent, 0),
    };
};
