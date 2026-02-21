import { normalizeListResponse } from '@/api/response';
import { apiClient } from '@/api/client';
import { mapAccountOrderListFromApi } from '@/mappers/account/orders';
import type { AccountOrderListParams, AccountOrderListResponse } from '@/types/account-orders';

export const listAccountOrders = async (params: AccountOrderListParams): Promise<AccountOrderListResponse> => {
    const { data } = await apiClient.get('/orders/me', {
        params,
    });
    const response = normalizeListResponse<unknown>(data);

    return {
        data: mapAccountOrderListFromApi(response.data),
        meta: response.meta,
    };
};
