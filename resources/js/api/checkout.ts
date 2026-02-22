import { apiClient } from "@/api/client";
import { extractData } from "@/api/response";
import { mapCheckoutOrderFromApi } from "@/mappers/checkout";
import type { CheckoutOrderResult, CheckoutPlaceOrderPayload } from "@/types/checkout";

export const placeCheckoutOrder = async (
    payload: CheckoutPlaceOrderPayload,
    idempotencyKey: string,
): Promise<CheckoutOrderResult | null> => {
    const { data } = await apiClient.post("/checkout/place-order", payload, {
        headers: {
            "Idempotency-Key": idempotencyKey,
        },
    });
    const response = extractData<unknown>(data);

    return response ? mapCheckoutOrderFromApi(response) : null;
};
