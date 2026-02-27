import { apiClient } from "@/api/client";
import { extractData, normalizeListResponse } from "@/api/response";
import {
    assertPromotionCouponWireDto,
    assertPromotionWireDto,
} from "@/contracts/api/v1/assertions/admin-promotions";
import {
    mapCouponFromApi,
    mapPromotionFromApi,
    mapPromotionListFromApi,
    toCouponCreateDto,
    toCouponUpdateDto,
    toPromotionMutationDto,
} from "@/mappers/admin/promotions";
import type {
    Coupon,
    CouponCreatePayload,
    CouponUpdatePayload,
    Promotion,
    PromotionListParams,
    PromotionListResponse,
    PromotionMutationPayload,
} from "@/types/admin-promotions";

interface ApiListRequestOptions {
    signal?: AbortSignal;
}

export const listPromotions = async (
    params: PromotionListParams,
    options?: ApiListRequestOptions,
): Promise<PromotionListResponse> => {
    const { data } = await apiClient.get("/admin/promotions", {
        params,
        signal: options?.signal,
    });

    const response = normalizeListResponse(data);

    return {
        data: mapPromotionListFromApi(response.data.map((item) => assertPromotionWireDto(item))),
        meta: response.meta,
    };
};

export const createPromotion = async (
    payload: PromotionMutationPayload,
): Promise<Promotion | null> => {
    const { data } = await apiClient.post("/admin/promotions", toPromotionMutationDto(payload));
    const response = extractData(data);

    if (response === null) {
        return null;
    }

    return mapPromotionFromApi(assertPromotionWireDto(response));
};

export const updatePromotion = async (
    promotionId: number,
    payload: PromotionMutationPayload,
): Promise<Promotion | null> => {
    const { data } = await apiClient.patch(
        `/admin/promotions/${promotionId}`,
        toPromotionMutationDto(payload),
    );
    const response = extractData(data);

    if (response === null) {
        return null;
    }

    return mapPromotionFromApi(assertPromotionWireDto(response));
};

export const deletePromotion = async (promotionId: number): Promise<void> => {
    await apiClient.delete(`/admin/promotions/${promotionId}`);
};

export const createPromotionCoupon = async (
    promotionId: number,
    payload: CouponCreatePayload,
): Promise<Coupon | null> => {
    const { data } = await apiClient.post(
        `/admin/promotions/${promotionId}/coupons`,
        toCouponCreateDto(payload),
    );
    const response = extractData(data);

    if (response === null) {
        return null;
    }

    return mapCouponFromApi(assertPromotionCouponWireDto(response));
};

export const updateCoupon = async (
    couponId: number,
    payload: CouponUpdatePayload,
): Promise<Coupon | null> => {
    const { data } = await apiClient.patch(
        `/admin/coupons/${couponId}`,
        toCouponUpdateDto(payload),
    );
    const response = extractData(data);

    if (response === null) {
        return null;
    }

    return mapCouponFromApi(assertPromotionCouponWireDto(response));
};
