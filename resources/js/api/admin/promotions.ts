import { apiClient } from '@/api/client';
import { extractData, normalizeListResponse } from '@/api/response';
import {
    mapCouponFromApi,
    mapPromotionFromApi,
    mapPromotionListFromApi,
    toCouponCreateDto,
    toCouponUpdateDto,
    toPromotionMutationDto,
} from '@/mappers/admin/promotions';
import type {
    Coupon,
    CouponCreatePayload,
    CouponUpdatePayload,
    Promotion,
    PromotionListParams,
    PromotionListResponse,
    PromotionMutationPayload,
} from '@/types/admin-promotions';

export const listPromotions = async (params: PromotionListParams): Promise<PromotionListResponse> => {
    const { data } = await apiClient.get('/admin/promotions', {
        params,
    });

    const response = normalizeListResponse<unknown>(data);

    return {
        data: mapPromotionListFromApi(response.data),
        meta: response.meta,
    };
};

export const createPromotion = async (payload: PromotionMutationPayload): Promise<Promotion | null> => {
    const { data } = await apiClient.post('/admin/promotions', toPromotionMutationDto(payload));
    const response = extractData<unknown>(data);

    return response ? mapPromotionFromApi(response) : null;
};

export const updatePromotion = async (promotionId: number, payload: PromotionMutationPayload): Promise<Promotion | null> => {
    const { data } = await apiClient.patch(`/admin/promotions/${promotionId}`, toPromotionMutationDto(payload));
    const response = extractData<unknown>(data);

    return response ? mapPromotionFromApi(response) : null;
};

export const deletePromotion = async (promotionId: number): Promise<void> => {
    await apiClient.delete(`/admin/promotions/${promotionId}`);
};

export const createPromotionCoupon = async (
    promotionId: number,
    payload: CouponCreatePayload,
): Promise<Coupon | null> => {
    const { data } = await apiClient.post(`/admin/promotions/${promotionId}/coupons`, toCouponCreateDto(payload));
    const response = extractData<unknown>(data);

    return response ? mapCouponFromApi(response) : null;
};

export const updateCoupon = async (couponId: number, payload: CouponUpdatePayload): Promise<Coupon | null> => {
    const { data } = await apiClient.patch(`/admin/coupons/${couponId}`, toCouponUpdateDto(payload));
    const response = extractData<unknown>(data);

    return response ? mapCouponFromApi(response) : null;
};
