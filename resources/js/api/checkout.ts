import { apiClient } from "@/api/client";
import { extractData } from "@/api/response";
import { assertCheckoutOrderWireDto } from "@/contracts/api/v1/assertions/checkout";
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
    const response = extractData(data);

    if (response === null) {
        return null;
    }

    return mapCheckoutOrderFromApi(assertCheckoutOrderWireDto(response));
};
