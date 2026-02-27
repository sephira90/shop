import { apiClient } from "@/api/client";
import { extractData } from "@/api/response";
import { assertCartWireDto } from "@/contracts/api/v1/assertions/cart";
import { mapCartFromApi, toCartUpsertItemRequestDto } from "@/mappers/cart";
import type { CartPayload, CartUpsertItemPayload } from "@/types/cart";

export const getCurrentCart = async (guestToken: string | null): Promise<CartPayload> => {
    const { data } = await apiClient.get("/cart", {
        params: guestToken ? { guest_token: guestToken } : {},
    });
    const response = extractData(data);

    return mapCartFromApi(assertCartWireDto(response));
};

export const upsertCartItem = async (payload: CartUpsertItemPayload): Promise<CartPayload> => {
    const { data } = await apiClient.post("/cart/items", toCartUpsertItemRequestDto(payload));
    const response = extractData(data);

    return mapCartFromApi(assertCartWireDto(response));
};

export const removeCartItem = async (
    productVariantId: number,
    guestToken: string | null,
): Promise<CartPayload> => {
    const { data } = await apiClient.delete(`/cart/items/${productVariantId}`, {
        params: guestToken ? { guest_token: guestToken } : {},
    });
    const response = extractData(data);

    return mapCartFromApi(assertCartWireDto(response));
};
